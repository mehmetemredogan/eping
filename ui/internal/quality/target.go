package quality

import (
	"net/url"

	"pinglab/ui/internal/api"
)

type Target struct {
	ID       uint64 `json:"id,omitempty"`
	Name     string `json:"name"`
	URL      string `json:"url"`
	Domain   string `json:"domain"`
	Category string `json:"category"`
}

// FetchTargets retrieves network quality targets dynamically from the ePing API.
func FetchTargets(client *api.Client) ([]Target, error) {
	apiTargets, err := client.QualityTargets()
	if err != nil {
		return nil, err
	}

	targets := make([]Target, 0, len(apiTargets))
	for _, t := range apiTargets {
		domain := t.Domain
		if domain == "" && t.URL != "" {
			if u, err := url.Parse(t.URL); err == nil {
				domain = u.Host
			}
		}
		targets = append(targets, Target{
			ID:       t.ID,
			Name:     t.Name,
			URL:      t.URL,
			Domain:   domain,
			Category: t.Category,
		})
	}

	return targets, nil
}
