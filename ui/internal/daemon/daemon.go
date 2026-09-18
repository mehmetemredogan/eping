package daemon

import (
	"context"
	crand "crypto/rand"
	"encoding/binary"
	"fmt"
	"os"
	"os/signal"
	"syscall"
	"time"

	"pinglab/ui/internal/api"
	"pinglab/ui/internal/config"
	"pinglab/ui/internal/linktype"
	"pinglab/ui/internal/quality"
)

type Options struct {
	Once bool
}

func randomInterval() time.Duration {
	minSec := int64(15 * 60) // 15 dakika = 900 saniye
	maxSec := int64(60 * 60) // 60 dakika = 3600 saniye

	b := make([]byte, 8)
	_, _ = crand.Read(b)
	val := int64(binary.LittleEndian.Uint64(b) & 0x7fffffffffffffff)
	sec := minSec + (val % (maxSec - minSec + 1))
	return time.Duration(sec) * time.Second
}

func Run(cfg config.Config, opts Options) error {
	client := api.New(cfg.APIURL, cfg.Token)
	connType := string(linktype.Detect())

	fmt.Println("\033[1;36m============================================================\033[0m")
	fmt.Println("  \033[1;37mEPING BACKGROUND DAEMON — AĞ KALİTE İZLEME SERVİSİ\033[0m")
	fmt.Println("\033[1;36m============================================================\033[0m")
	fmt.Printf("  API Adresi : %s\n", cfg.APIURL)
	if cfg.Token != "" {
		fmt.Printf("  Kullanıcı  : %s (Oturum Açık)\n", cfg.Username)
	} else {
		fmt.Printf("  Kullanıcı  : Anonim (Yalnızca yerel ölçüm / sunucuya kaydetme devre dışı)\n")
	}
	fmt.Println("  Periyot    : Rastgele (Her ölçümde 15–60 dakika arası dinamik)")
	fmt.Printf("  Bağlantı   : %s\n", connType)
	fmt.Println("  Çıkmak için Ctrl+C tuşlarına basın.")
	fmt.Println("\033[1;36m============================================================\033[0m")
	fmt.Println()

	ctx, cancel := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer cancel()

	runCycle := func() {
		now := time.Now()
		targets, err := quality.FetchTargets(client)
		if err != nil {
			fmt.Printf("[%s] \033[0;31mHedef listesi API'den alınamadı: %v\033[0m\n", now.Format("15:04:05"), err)
			return
		}
		if len(targets) == 0 {
			fmt.Printf("[%s] \033[0;33mTanımlı ağ kalitesi hedefi bulunamadı. Lütfen önce admin panelinden hedefler ekleyin.\033[0m\n", now.Format("15:04:05"))
			return
		}

		fmt.Printf("[%s] Ağ kalite ölçümü başlatılıyor (%d hedef)...\n", now.Format("15:04:05"), len(targets))

		results := quality.MeasureAll(ctx, targets, quality.MeasureOptions{
			Timeout:     6 * time.Second,
			Concurrency: 4,
		})
		eval := quality.Evaluate(results)

		ttfbStr := "—"
		if eval.AvgTTFBMs != nil {
			ttfbStr = fmt.Sprintf("%0.1fms", *eval.AvgTTFBMs)
		}
		latStr := "—"
		if eval.AvgLatencyMs != nil {
			latStr = fmt.Sprintf("%0.1fms", *eval.AvgLatencyMs)
		}

		fmt.Printf("[%s] Ölçüm Tamamlandı: Puan: %d [%s] (%s) | TTFB: %s | Gecikme: %s | Başarı: %d/%d (%%%0.1f kayıp)\n",
			time.Now().Format("15:04:05"),
			eval.Score,
			eval.Grade,
			eval.Status,
			ttfbStr,
			latStr,
			eval.TargetsSuccess,
			eval.TargetsTotal,
			eval.PacketLossPercent,
		)

		if cfg.Token != "" {
			payload := api.NetworkQualityPayload{
				Score:             eval.Score,
				Grade:             eval.Grade,
				Status:            eval.Status,
				Summary:           eval.Summary,
				AvgLatencyMs:      eval.AvgLatencyMs,
				AvgDNSMs:          eval.AvgDNSMs,
				AvgTCPMs:          eval.AvgTCPMs,
				AvgTLSMs:          eval.AvgTLSMs,
				AvgTTFBMs:         eval.AvgTTFBMs,
				PacketLossPercent: eval.PacketLossPercent,
				ConnectionType:    connType,
				Results:           results,
				Insights:          eval.Insights,
				TestedAt:          now.UTC().Format(time.RFC3339),
			}

			if err := client.StoreNetworkQuality(payload); err != nil {
				fmt.Printf("[%s] \033[0;31mSunucuya kaydetme hatası: %v\033[0m\n", time.Now().Format("15:04:05"), err)
			} else {
				fmt.Printf("[%s] \033[0;32mSonuçlar platforma başarıyla kaydedildi.\033[0m\n", time.Now().Format("15:04:05"))
			}
		} else {
			fmt.Printf("[%s] \033[0;33mOturum kapalı; sonuçlar sunucuya gönderilmedi. Giriş yapmak için 'eping' TUI kullanabilirsiniz.\033[0m\n", time.Now().Format("15:04:05"))
		}
	}

	// First cycle immediately
	runCycle()
	if opts.Once {
		return nil
	}

	for {
		interval := randomInterval()
		nextTime := time.Now().Add(interval)
		fmt.Printf("[%s] Bir sonraki ağ kalite ölçümü %s sonra (%s) yapılacak.\n\n",
			time.Now().Format("15:04:05"),
			interval.Round(time.Second),
			nextTime.Format("15:04:05"),
		)

		timer := time.NewTimer(interval)
		select {
		case <-ctx.Done():
			timer.Stop()
			fmt.Println("\nDaemon sonlandırıldı.")
			return nil
		case <-timer.C:
			runCycle()
		}
	}
}
