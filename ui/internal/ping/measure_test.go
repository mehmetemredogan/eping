package ping_test

import (
	"context"
	"pinglab/ui/internal/ping"
	"testing"
)

func TestMeasureHTTPSmoke(t *testing.T) {
	st := ping.MeasureHTTP(context.Background(), "https://www.cloudflare.com", 2)
	if st.Samples != 2 {
		t.Fatalf("samples=%d", st.Samples)
	}
	if st.SuccessRate <= 0 {
		t.Fatalf("expected some success, got %.2f", st.SuccessRate)
	}
	t.Logf("avg=%s quality=%s success=%.2f", ping.FormatMs(st.AvgMs), st.Quality, st.SuccessRate)
}
