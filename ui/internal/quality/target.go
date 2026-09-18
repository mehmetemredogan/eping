package quality

type Target struct {
	Name     string `json:"name"`
	URL      string `json:"url"`
	Domain   string `json:"domain"`
	Category string `json:"category"` // search, cdn, cloud, dev, streaming, ecommerce, gov, hosting
}

// DefaultTargets returns a diverse suite of major global and regional web services.
func DefaultTargets() []Target {
	return []Target{
		{
			Name:     "Cloudflare CDN",
			URL:      "https://cloudflare.com",
			Domain:   "cloudflare.com",
			Category: "cdn",
		},
		{
			Name:     "Google Search",
			URL:      "https://www.google.com",
			Domain:   "www.google.com",
			Category: "search",
		},
		{
			Name:     "Microsoft",
			URL:      "https://www.microsoft.com",
			Domain:   "www.microsoft.com",
			Category: "cloud",
		},
		{
			Name:     "Apple",
			URL:      "https://www.apple.com",
			Domain:   "www.apple.com",
			Category: "cloud",
		},
		{
			Name:     "GitHub",
			URL:      "https://github.com",
			Domain:   "github.com",
			Category: "dev",
		},
		{
			Name:     "Amazon AWS",
			URL:      "https://aws.amazon.com",
			Domain:   "aws.amazon.com",
			Category: "cloud",
		},
		{
			Name:     "Wikipedia",
			URL:      "https://www.wikipedia.org",
			Domain:   "www.wikipedia.org",
			Category: "content",
		},
		{
			Name:     "YouTube",
			URL:      "https://www.youtube.com",
			Domain:   "www.youtube.com",
			Category: "streaming",
		},
		{
			Name:     "Netflix",
			URL:      "https://www.netflix.com",
			Domain:   "www.netflix.com",
			Category: "streaming",
		},
		{
			Name:     "e-Devlet",
			URL:      "https://www.turkiye.gov.tr",
			Domain:   "www.turkiye.gov.tr",
			Category: "gov",
		},
		{
			Name:     "Trendyol",
			URL:      "https://www.trendyol.com",
			Domain:   "www.trendyol.com",
			Category: "ecommerce",
		},
		{
			Name:     "Hetzner",
			URL:      "https://www.hetzner.com",
			Domain:   "www.hetzner.com",
			Category: "hosting",
		},
	}
}
