package main

import (
	"context"
	"flag"
	"fmt"
	"os"
	"time"

	"pinglab/ui/internal/api"
	"pinglab/ui/internal/config"
	"pinglab/ui/internal/daemon"
	"pinglab/ui/internal/linktype"
	"pinglab/ui/internal/quality"
	uiapp "pinglab/ui/internal/ui"
)

// version is set at build time via -ldflags "-X main.version=...".
// See ui/Makefile, ui/build.sh, ui/build.ps1 and .github/workflows/ui-release.yml.
var version = "dev"

func printUsage() {
	fmt.Printf(`eping %s — Modern Ağ Analiz ve Kalite Test Platformu

KULLANIM:
  eping                    İnteraktif terminal arayüzünü (TUI) başlatır
  eping quality [bayraklar] Gerçek web servisleri üzerinden ağ kalitesini test eder
  eping daemon [bayraklar]  Arkaplanda periyodik olarak ağ kalitesini izler ve raporlar
  eping version            Versiyon bilgisini gösterir

KALİTE TESTİ (eping quality):
  --json                   Sonuçları JSON formatında yazdırır
  --no-upload              Sonuçları API sunucusuna kaydetmez (yerel rapor)
  --timeout <süre>         Her bir hedef için zaman aşımı (varsayılan: 6s)

DAEMON MODU (eping daemon):
  --once                   Yalnızca tek bir ölçüm döngüsü çalıştırır ve çıkar
  (Ölçüm aralığı dinamiktir: her ölçümde 15–60 dk arasında rastgele belirlenir)
`, version)
}

func runQuality(args []string) {
	fs := flag.NewFlagSet("quality", flag.ExitOnError)
	jsonOut := fs.Bool("json", false, "JSON çıktısı üretir")
	noUpload := fs.Bool("no-upload", false, "Sunucuya kaydetmeyi atlar")
	timeout := fs.Duration("timeout", 6*time.Second, "Hedef başına zaman aşımı")
	_ = fs.Parse(args)

	cfg := config.Load()
	client := api.New(cfg.APIURL, cfg.Token)
	targets, err := quality.FetchTargets(client)
	if err != nil {
		if *jsonOut {
			fmt.Fprintf(os.Stderr, "{\"error\": %q}\n", err.Error())
		} else {
			fmt.Printf("  \033[0;31m[!] Hedef listesi API'den alınamadı: %v\033[0m\n", err)
		}
		os.Exit(1)
	}

	if len(targets) == 0 {
		if *jsonOut {
			fmt.Println("{\"targets\": [], \"message\": \"no active targets\"}")
		} else {
			fmt.Println("  \033[0;33m[!] Tanımlı ağ kalitesi hedefi bulunamadı. Lütfen önce admin panelinden (Ağ Kalitesi Hedefleri) hedefler ekleyin.\033[0m")
		}
		return
	}

	if !*jsonOut {
		fmt.Printf("Ağ kalite testi başlatılıyor (%d web hedefi taranıyor)...\n", len(targets))
	}

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	results := quality.MeasureAll(ctx, targets, quality.MeasureOptions{
		Timeout:     *timeout,
		Concurrency: 4,
	})
	eval := quality.Evaluate(results)
	now := time.Now()

	rep := quality.Report{
		TestedAt:   now,
		Evaluation: eval,
		Results:    results,
	}

	if *jsonOut {
		_ = quality.PrintJSON(os.Stdout, rep)
	} else {
		quality.PrintTerminal(os.Stdout, rep)
	}

	if !*noUpload && cfg.Token != "" {
		client := api.New(cfg.APIURL, cfg.Token)
		connType := string(linktype.Detect())
		err := client.StoreNetworkQuality(api.NetworkQualityPayload{
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
		})
		if !*jsonOut {
			if err != nil {
				fmt.Printf("  \033[0;31m[!] Sunucuya kaydetme hatası: %v\033[0m\n\n", err)
			} else {
				fmt.Printf("  \033[0;32m[✓] Test sonucu platforma kaydedildi: %s/quality\033[0m\n\n", cfg.APIURL)
			}
		}
	} else if !*jsonOut && cfg.Token == "" {
		fmt.Println("  \033[0;33m[i] Oturum açılmadığı için sonuçlar yerel olarak gösterildi. Kaydetmek için 'eping' ile giriş yapın.\033[0m")
		fmt.Println()
	}
}

func runDaemon(args []string) {
	fs := flag.NewFlagSet("daemon", flag.ExitOnError)
	once := fs.Bool("once", false, "Tek seferlik çalıştırıp çık")
	_ = fs.Parse(args)

	cfg := config.Load()
	if err := daemon.Run(cfg, daemon.Options{
		Once: *once,
	}); err != nil {
		fmt.Fprintf(os.Stderr, "Daemon hatası: %v\n", err)
		os.Exit(1)
	}
}

func main() {
	if len(os.Args) > 1 {
		switch os.Args[1] {
		case "-v", "--version", "version":
			fmt.Println("eping " + version)
			return
		case "-h", "--help", "help":
			printUsage()
			return
		case "quality", "q":
			runQuality(os.Args[2:])
			return
		case "daemon", "d":
			runDaemon(os.Args[2:])
			return
		}
	}

	cfg := config.Load()
	if err := uiapp.Run(cfg); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}
}
