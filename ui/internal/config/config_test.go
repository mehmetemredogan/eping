package config_test

import (
	"os"
	"path/filepath"
	"runtime"
	"testing"

	"pinglab/ui/internal/config"
)

func setConfigHome(t *testing.T, dir string) {
	t.Helper()
	switch runtime.GOOS {
	case "windows":
		t.Setenv("AppData", dir)
	case "darwin":
		t.Setenv("HOME", dir)
	default:
		t.Setenv("XDG_CONFIG_HOME", dir)
	}
}

func TestSaveLoadRoundTrip(t *testing.T) {
	setConfigHome(t, t.TempDir())

	cfg := config.Default()
	cfg.APIURL = "http://example.test"
	cfg.Token = "tok123"
	cfg.Username = "alice"

	if err := cfg.Save(); err != nil {
		t.Fatalf("Save: %v", err)
	}

	loaded := config.Load()
	if loaded.APIURL != cfg.APIURL || loaded.Token != cfg.Token || loaded.Username != cfg.Username {
		t.Fatalf("round trip mismatch: got %+v want %+v", loaded, cfg)
	}
}

func TestLoadCorruptFileFallsBackToDefaults(t *testing.T) {
	dir := t.TempDir()
	setConfigHome(t, dir)

	path := filepath.Join(dir, "eping", "config.yaml")
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		t.Fatal(err)
	}
	// Not valid YAML for our struct (a raw scalar, not a mapping).
	if err := os.WriteFile(path, []byte("::: not yaml :::"), 0o600); err != nil {
		t.Fatal(err)
	}

	loaded := config.Load()
	if loaded.APIURL == "" {
		t.Fatal("expected default APIURL to survive a corrupt config file")
	}
}

func TestLoadEmptyAPIURLRestoresDefault(t *testing.T) {
	dir := t.TempDir()
	setConfigHome(t, dir)

	path := filepath.Join(dir, "eping", "config.yaml")
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(path, []byte("api_url: \"\"\nsamples: 4\nconcurrency: 6\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	loaded := config.Load()
	if loaded.APIURL == "" {
		t.Fatal("expected empty api_url in file to be replaced by the default")
	}
}
