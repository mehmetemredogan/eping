package config

import (
	"os"
	"path/filepath"
	"strings"

	"gopkg.in/yaml.v3"
)

// DefaultAPIURL is the production API baked into the binary. Used when there
// is no config.yaml, when api_url is empty, or when the UI field is cleared.
// Override at runtime via the TUI API field (Enter saves to config.yaml), by
// editing config.yaml, or with EPING_API_URL / PINGLAB_API_URL.
const DefaultAPIURL = "https://ping.mehmetemredogan.tr"

type Config struct {
	APIURL         string `yaml:"api_url"`
	Samples        int    `yaml:"samples"`
	Concurrency    int    `yaml:"concurrency"`
	Token          string `yaml:"token"`
	Username       string `yaml:"username"`
	TraceOnMeasure bool   `yaml:"trace_on_measure"` // single-target: run traceroute
	TraceOnAll     bool   `yaml:"trace_on_all"`     // test-all: also traceroute (slow)
}

func Default() Config {
	return Config{
		APIURL:         envOr("EPING_API_URL", envOr("PINGLAB_API_URL", DefaultAPIURL)),
		Samples:        4,
		Concurrency:    6,
		TraceOnMeasure: true,
		TraceOnAll:     false,
	}
}

func Load() Config {
	cfg := Default()
	path := configPath()
	data, err := os.ReadFile(path)
	if err != nil {
		return cfg
	}
	// A corrupt/partial config file must not silently zero out the struct —
	// keep the just-computed defaults if it fails to parse.
	if err := yaml.Unmarshal(data, &cfg); err != nil {
		return Default()
	}
	if strings.TrimSpace(cfg.APIURL) == "" {
		cfg.APIURL = DefaultAPIURL
	}
	if v := os.Getenv("EPING_API_URL"); v != "" {
		cfg.APIURL = v
	} else if v := os.Getenv("PINGLAB_API_URL"); v != "" {
		cfg.APIURL = v
	}
	if cfg.Samples < 1 {
		cfg.Samples = 4
	}
	if cfg.Concurrency < 1 {
		cfg.Concurrency = 6
	}
	return cfg
}

// Save writes the config atomically (temp file + rename) so a crash or
// concurrent write never leaves a truncated/corrupt config.yaml behind.
func (c Config) Save() error {
	path := configPath()
	dir := filepath.Dir(path)
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return err
	}
	data, err := yaml.Marshal(c)
	if err != nil {
		return err
	}
	tmp, err := os.CreateTemp(dir, ".config-*.yaml.tmp")
	if err != nil {
		return err
	}
	tmpPath := tmp.Name()
	if _, err := tmp.Write(data); err != nil {
		tmp.Close()
		os.Remove(tmpPath)
		return err
	}
	if err := tmp.Close(); err != nil {
		os.Remove(tmpPath)
		return err
	}
	if err := os.Chmod(tmpPath, 0o600); err != nil {
		os.Remove(tmpPath)
		return err
	}
	if err := os.Rename(tmpPath, path); err != nil {
		os.Remove(tmpPath)
		return err
	}
	return nil
}

func configPath() string {
	if home, err := os.UserConfigDir(); err == nil {
		return filepath.Join(home, "eping", "config.yaml")
	}
	return "config.yaml"
}

func envOr(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
