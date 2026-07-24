<?php

namespace Database\Seeders;

use App\Models\PingTarget;
use Illuminate\Database\Seeder;

class PingTargetSeeder extends Seeder
{
    public function run(): void
    {
        $targets = array_merge(
            $this->aws(),
            $this->azure(),
            $this->gcp(),
            $this->cloudflare(),
            $this->digitalocean(),
            $this->oracle(),
            $this->alibaba(),
            $this->hetzner(),
            $this->vultr(),
            $this->linode(),
            $this->ovh(),
            $this->scaleway(),
            $this->cdn(),
            $this->dns(),
            $this->gamingPlatforms(),
            $this->gameServers(),
        );

        foreach ($targets as $i => $target) {
            PingTarget::updateOrCreate(
                ['host' => $target['host']],
                array_merge([
                    'is_active' => true,
                    'description' => null,
                    'sort_order' => ($target['sort_order'] ?? 1000) + $i,
                ], $target)
            );
        }
    }

    private function row(
        string $name,
        string $host,
        string $category,
        string $provider,
        ?string $location,
        ?string $country,
        int $sort
    ): array {
        return [
            'name' => $name,
            'host' => $host,
            'category' => $category,
            'provider' => $provider,
            'location' => $location,
            'country_code' => $country,
            'sort_order' => $sort,
        ];
    }

    private function aws(): array
    {
        $regions = [
            ['US East (N. Virginia)', 'us-east-1', 'N. Virginia, USA', 'us'],
            ['US East (Ohio)', 'us-east-2', 'Ohio, USA', 'us'],
            ['US West (N. California)', 'us-west-1', 'N. California, USA', 'us'],
            ['US West (Oregon)', 'us-west-2', 'Oregon, USA', 'us'],
            ['Canada (Central)', 'ca-central-1', 'Montreal, Canada', 'ca'],
            ['Canada West (Calgary)', 'ca-west-1', 'Calgary, Canada', 'ca'],
            ['EU (Ireland)', 'eu-west-1', 'Ireland', 'ie'],
            ['EU (London)', 'eu-west-2', 'London, UK', 'gb'],
            ['EU (Paris)', 'eu-west-3', 'Paris, France', 'fr'],
            ['EU (Frankfurt)', 'eu-central-1', 'Frankfurt, Germany', 'de'],
            ['EU (Zurich)', 'eu-central-2', 'Zurich, Switzerland', 'ch'],
            ['EU (Stockholm)', 'eu-north-1', 'Stockholm, Sweden', 'se'],
            ['EU (Milan)', 'eu-south-1', 'Milan, Italy', 'it'],
            ['EU (Spain)', 'eu-south-2', 'Spain', 'es'],
            ['Asia (Tokyo)', 'ap-northeast-1', 'Tokyo, Japan', 'jp'],
            ['Asia (Osaka)', 'ap-northeast-3', 'Osaka, Japan', 'jp'],
            ['Asia (Seoul)', 'ap-northeast-2', 'Seoul, South Korea', 'kr'],
            ['Asia (Singapore)', 'ap-southeast-1', 'Singapore', 'sg'],
            ['Asia (Sydney)', 'ap-southeast-2', 'Sydney, Australia', 'au'],
            ['Asia (Jakarta)', 'ap-southeast-3', 'Jakarta, Indonesia', 'id'],
            ['Asia (Melbourne)', 'ap-southeast-4', 'Melbourne, Australia', 'au'],
            ['Asia (Hong Kong)', 'ap-east-1', 'Hong Kong', 'hk'],
            ['Asia (Mumbai)', 'ap-south-1', 'Mumbai, India', 'in'],
            ['Asia (Hyderabad)', 'ap-south-2', 'Hyderabad, India', 'in'],
            ['South America (Sao Paulo)', 'sa-east-1', 'Sao Paulo, Brazil', 'br'],
            ['Africa (Cape Town)', 'af-south-1', 'Cape Town, South Africa', 'za'],
            ['Middle East (Bahrain)', 'me-south-1', 'Bahrain', 'bh'],
            ['Middle East (UAE)', 'me-central-1', 'UAE', 'ae'],
            ['Israel (Tel Aviv)', 'il-central-1', 'Tel Aviv, Israel', 'il'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "AWS {$r[0]}",
                "ec2.{$r[1]}.amazonaws.com",
                'aws',
                'Amazon Web Services',
                $r[2],
                $r[3],
                10 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function azure(): array
    {
        $regions = [
            ['East US', 'eastus', 'Virginia, USA', 'us'],
            ['East US 2', 'eastus2', 'Virginia, USA', 'us'],
            ['West US', 'westus', 'California, USA', 'us'],
            ['West US 2', 'westus2', 'Washington, USA', 'us'],
            ['West US 3', 'westus3', 'Arizona, USA', 'us'],
            ['Central US', 'centralus', 'Iowa, USA', 'us'],
            ['North Central US', 'northcentralus', 'Illinois, USA', 'us'],
            ['South Central US', 'southcentralus', 'Texas, USA', 'us'],
            ['Canada Central', 'canadacentral', 'Toronto, Canada', 'ca'],
            ['Canada East', 'canadaeast', 'Quebec, Canada', 'ca'],
            ['West Europe', 'westeurope', 'Netherlands', 'nl'],
            ['North Europe', 'northeurope', 'Ireland', 'ie'],
            ['UK South', 'uksouth', 'London, UK', 'gb'],
            ['UK West', 'ukwest', 'Cardiff, UK', 'gb'],
            ['France Central', 'francecentral', 'Paris, France', 'fr'],
            ['Germany West Central', 'germanywestcentral', 'Frankfurt, Germany', 'de'],
            ['Switzerland North', 'switzerlandnorth', 'Zurich, Switzerland', 'ch'],
            ['Norway East', 'norwayeast', 'Oslo, Norway', 'no'],
            ['Sweden Central', 'swedencentral', 'Gavle, Sweden', 'se'],
            ['Italy North', 'italynorth', 'Milan, Italy', 'it'],
            ['Spain Central', 'spaincentral', 'Madrid, Spain', 'es'],
            ['Poland Central', 'polandcentral', 'Warsaw, Poland', 'pl'],
            ['Japan East', 'japaneast', 'Tokyo, Japan', 'jp'],
            ['Japan West', 'japanwest', 'Osaka, Japan', 'jp'],
            ['Korea Central', 'koreacentral', 'Seoul, South Korea', 'kr'],
            ['Southeast Asia', 'southeastasia', 'Singapore', 'sg'],
            ['East Asia', 'eastasia', 'Hong Kong', 'hk'],
            ['Australia East', 'australiaeast', 'Sydney, Australia', 'au'],
            ['Australia Southeast', 'australiasoutheast', 'Melbourne, Australia', 'au'],
            ['Central India', 'centralindia', 'Pune, India', 'in'],
            ['South India', 'southindia', 'Chennai, India', 'in'],
            ['Brazil South', 'brazilsouth', 'Sao Paulo, Brazil', 'br'],
            ['UAE North', 'uaenorth', 'Dubai, UAE', 'ae'],
            ['South Africa North', 'southafricanorth', 'Johannesburg, South Africa', 'za'],
            ['Qatar Central', 'qatarcentral', 'Doha, Qatar', 'qa'],
            ['Israel Central', 'israelcentral', 'Israel', 'il'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "Azure {$r[0]}",
                "{$r[1]}.blob.core.windows.net",
                'azure',
                'Microsoft Azure',
                $r[2],
                $r[3],
                100 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function gcp(): array
    {
        $regions = [
            ['US Central (Iowa)', 'us-central1', 'Iowa, USA', 'us'],
            ['US East (S. Carolina)', 'us-east1', 'South Carolina, USA', 'us'],
            ['US East (Virginia)', 'us-east4', 'Virginia, USA', 'us'],
            ['US West (Oregon)', 'us-west1', 'Oregon, USA', 'us'],
            ['US West (Los Angeles)', 'us-west2', 'Los Angeles, USA', 'us'],
            ['US West (Salt Lake City)', 'us-west3', 'Utah, USA', 'us'],
            ['US West (Las Vegas)', 'us-west4', 'Las Vegas, USA', 'us'],
            ['Europe West (Belgium)', 'europe-west1', 'Belgium', 'be'],
            ['Europe West (London)', 'europe-west2', 'London, UK', 'gb'],
            ['Europe West (Frankfurt)', 'europe-west3', 'Frankfurt, Germany', 'de'],
            ['Europe West (Netherlands)', 'europe-west4', 'Netherlands', 'nl'],
            ['Europe West (Zurich)', 'europe-west6', 'Zurich, Switzerland', 'ch'],
            ['Europe West (Milan)', 'europe-west8', 'Milan, Italy', 'it'],
            ['Europe West (Paris)', 'europe-west9', 'Paris, France', 'fr'],
            ['Europe North (Finland)', 'europe-north1', 'Finland', 'fi'],
            ['Europe Central (Warsaw)', 'europe-central2', 'Warsaw, Poland', 'pl'],
            ['Asia East (Taiwan)', 'asia-east1', 'Taiwan', 'tw'],
            ['Asia East (Hong Kong)', 'asia-east2', 'Hong Kong', 'hk'],
            ['Asia Northeast (Tokyo)', 'asia-northeast1', 'Tokyo, Japan', 'jp'],
            ['Asia Northeast (Osaka)', 'asia-northeast2', 'Osaka, Japan', 'jp'],
            ['Asia Northeast (Seoul)', 'asia-northeast3', 'Seoul, South Korea', 'kr'],
            ['Asia South (Mumbai)', 'asia-south1', 'Mumbai, India', 'in'],
            ['Asia Southeast (Singapore)', 'asia-southeast1', 'Singapore', 'sg'],
            ['Asia Southeast (Jakarta)', 'asia-southeast2', 'Jakarta, Indonesia', 'id'],
            ['Australia (Sydney)', 'australia-southeast1', 'Sydney, Australia', 'au'],
            ['Australia (Melbourne)', 'australia-southeast2', 'Melbourne, Australia', 'au'],
            ['South America (Sao Paulo)', 'southamerica-east1', 'Sao Paulo, Brazil', 'br'],
            ['Middle East (Tel Aviv)', 'me-west1', 'Tel Aviv, Israel', 'il'],
            ['Africa (Johannesburg)', 'africa-south1', 'Johannesburg, South Africa', 'za'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "GCP {$r[0]}",
                "{$r[1]}-run.googleapis.com",
                'gcp',
                'Google Cloud',
                $r[2],
                $r[3],
                200 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function digitalocean(): array
    {
        $spaces = [
            ['NYC1', 'nyc1', 'New York, USA', 'us'],
            ['NYC3', 'nyc3', 'New York, USA', 'us'],
            ['SFO2', 'sfo2', 'San Francisco, USA', 'us'],
            ['SFO3', 'sfo3', 'San Francisco, USA', 'us'],
            ['AMS3', 'ams3', 'Amsterdam, Netherlands', 'nl'],
            ['SGP1', 'sgp1', 'Singapore', 'sg'],
            ['LON1', 'lon1', 'London, UK', 'gb'],
            ['FRA1', 'fra1', 'Frankfurt, Germany', 'de'],
            ['TOR1', 'tor1', 'Toronto, Canada', 'ca'],
            ['BLR1', 'blr1', 'Bangalore, India', 'in'],
            ['SYD1', 'syd1', 'Sydney, Australia', 'au'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "DigitalOcean {$r[0]}",
                "{$r[1]}.digitaloceanspaces.com",
                'digitalocean',
                'DigitalOcean',
                $r[2],
                $r[3],
                300 + $i
            ),
            $spaces,
            array_keys($spaces)
        );
    }

    private function oracle(): array
    {
        $regions = [
            ['US Ashburn', 'us-ashburn-1', 'Ashburn, USA', 'us'],
            ['US Phoenix', 'us-phoenix-1', 'Phoenix, USA', 'us'],
            ['US Chicago', 'us-chicago-1', 'Chicago, USA', 'us'],
            ['US San Jose', 'us-sanjose-1', 'San Jose, USA', 'us'],
            ['Canada Montreal', 'ca-montreal-1', 'Montreal, Canada', 'ca'],
            ['Canada Toronto', 'ca-toronto-1', 'Toronto, Canada', 'ca'],
            ['EU Frankfurt', 'eu-frankfurt-1', 'Frankfurt, Germany', 'de'],
            ['EU Amsterdam', 'eu-amsterdam-1', 'Amsterdam, Netherlands', 'nl'],
            ['EU London', 'uk-london-1', 'London, UK', 'gb'],
            ['EU Paris', 'eu-paris-1', 'Paris, France', 'fr'],
            ['EU Zurich', 'eu-zurich-1', 'Zurich, Switzerland', 'ch'],
            ['EU Milan', 'eu-milan-1', 'Milan, Italy', 'it'],
            ['EU Stockholm', 'eu-stockholm-1', 'Stockholm, Sweden', 'se'],
            ['EU Madrid', 'eu-madrid-1', 'Madrid, Spain', 'es'],
            ['AP Tokyo', 'ap-tokyo-1', 'Tokyo, Japan', 'jp'],
            ['AP Osaka', 'ap-osaka-1', 'Osaka, Japan', 'jp'],
            ['AP Seoul', 'ap-seoul-1', 'Seoul, South Korea', 'kr'],
            ['AP Singapore', 'ap-singapore-1', 'Singapore', 'sg'],
            ['AP Mumbai', 'ap-mumbai-1', 'Mumbai, India', 'in'],
            ['AP Sydney', 'ap-sydney-1', 'Sydney, Australia', 'au'],
            ['AP Melbourne', 'ap-melbourne-1', 'Melbourne, Australia', 'au'],
            ['SA Sao Paulo', 'sa-saopaulo-1', 'Sao Paulo, Brazil', 'br'],
            ['ME Dubai', 'me-dubai-1', 'Dubai, UAE', 'ae'],
            ['ME Jeddah', 'me-jeddah-1', 'Jeddah, Saudi Arabia', 'sa'],
            ['AF Johannesburg', 'af-johannesburg-1', 'Johannesburg, South Africa', 'za'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "Oracle {$r[0]}",
                "objectstorage.{$r[1]}.oraclecloud.com",
                'oracle',
                'Oracle Cloud',
                $r[2],
                $r[3],
                400 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function alibaba(): array
    {
        $regions = [
            ['CN Hangzhou', 'oss-cn-hangzhou', 'Hangzhou, China', 'cn'],
            ['CN Shanghai', 'oss-cn-shanghai', 'Shanghai, China', 'cn'],
            ['CN Beijing', 'oss-cn-beijing', 'Beijing, China', 'cn'],
            ['CN Shenzhen', 'oss-cn-shenzhen', 'Shenzhen, China', 'cn'],
            ['CN Hong Kong', 'oss-cn-hongkong', 'Hong Kong', 'hk'],
            ['US Silicon Valley', 'oss-us-west-1', 'Silicon Valley, USA', 'us'],
            ['US Virginia', 'oss-us-east-1', 'Virginia, USA', 'us'],
            ['EU Frankfurt', 'oss-eu-central-1', 'Frankfurt, Germany', 'de'],
            ['EU London', 'oss-eu-west-1', 'London, UK', 'gb'],
            ['AP Singapore', 'oss-ap-southeast-1', 'Singapore', 'sg'],
            ['AP Sydney', 'oss-ap-southeast-2', 'Sydney, Australia', 'au'],
            ['AP Kuala Lumpur', 'oss-ap-southeast-3', 'Kuala Lumpur, Malaysia', 'my'],
            ['AP Jakarta', 'oss-ap-southeast-5', 'Jakarta, Indonesia', 'id'],
            ['AP Tokyo', 'oss-ap-northeast-1', 'Tokyo, Japan', 'jp'],
            ['AP Seoul', 'oss-ap-northeast-2', 'Seoul, South Korea', 'kr'],
            ['AP Mumbai', 'oss-ap-south-1', 'Mumbai, India', 'in'],
            ['ME Dubai', 'oss-me-east-1', 'Dubai, UAE', 'ae'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "Alibaba {$r[0]}",
                "{$r[1]}.aliyuncs.com",
                'alibaba',
                'Alibaba Cloud',
                $r[2],
                $r[3],
                500 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function cloudflare(): array
    {
        return [
            $this->row('Cloudflare DNS 1.1.1.1', '1.1.1.1', 'cloudflare', 'Cloudflare', 'Global Anycast', null, 600),
            $this->row('Cloudflare DNS 1.0.0.1', '1.0.0.1', 'cloudflare', 'Cloudflare', 'Global Anycast', null, 601),
            $this->row('Cloudflare DNS.com', 'cloudflare-dns.com', 'cloudflare', 'Cloudflare', 'Global Anycast', null, 602),
            $this->row('Cloudflare.com', 'www.cloudflare.com', 'cloudflare', 'Cloudflare', 'Global CDN', null, 603),
            $this->row('Cloudflare Speed', 'speed.cloudflare.com', 'cloudflare', 'Cloudflare', 'Global CDN', null, 604),
        ];
    }

    private function hetzner(): array
    {
        return [
            $this->row('Hetzner Falkenstein', 'fsn1-speed.hetzner.com', 'hetzner', 'Hetzner', 'Falkenstein, Germany', 'de', 700),
            $this->row('Hetzner Nuremberg', 'nbg1-speed.hetzner.com', 'hetzner', 'Hetzner', 'Nuremberg, Germany', 'de', 701),
            $this->row('Hetzner Helsinki', 'hel1-speed.hetzner.com', 'hetzner', 'Hetzner', 'Helsinki, Finland', 'fi', 702),
            $this->row('Hetzner Ashburn', 'ash-speed.hetzner.com', 'hetzner', 'Hetzner', 'Ashburn, USA', 'us', 703),
            $this->row('Hetzner Hillsboro', 'hil-speed.hetzner.com', 'hetzner', 'Hetzner', 'Hillsboro, USA', 'us', 704),
            $this->row('Hetzner Cloud', 'www.hetzner.com', 'hetzner', 'Hetzner', 'Germany', 'de', 705),
        ];
    }

    private function vultr(): array
    {
        $regions = [
            ['New Jersey', 'nj', 'New Jersey, USA', 'us'],
            ['Chicago', 'il', 'Chicago, USA', 'us'],
            ['Dallas', 'tx', 'Dallas, USA', 'us'],
            ['Seattle', 'wa', 'Seattle, USA', 'us'],
            ['Los Angeles', 'lax', 'Los Angeles, USA', 'us'],
            ['Atlanta', 'ga', 'Atlanta, USA', 'us'],
            ['Miami', 'fl', 'Miami, USA', 'us'],
            ['Toronto', 'tor', 'Toronto, Canada', 'ca'],
            ['Amsterdam', 'ams', 'Amsterdam, Netherlands', 'nl'],
            ['London', 'lon', 'London, UK', 'gb'],
            ['Frankfurt', 'fra', 'Frankfurt, Germany', 'de'],
            ['Paris', 'par', 'Paris, France', 'fr'],
            ['Stockholm', 'sto', 'Stockholm, Sweden', 'se'],
            ['Madrid', 'mad', 'Madrid, Spain', 'es'],
            ['Warsaw', 'waw', 'Warsaw, Poland', 'pl'],
            ['Singapore', 'sgp', 'Singapore', 'sg'],
            ['Tokyo', 'tyo', 'Tokyo, Japan', 'jp'],
            ['Seoul', 'icn', 'Seoul, South Korea', 'kr'],
            ['Sydney', 'syd', 'Sydney, Australia', 'au'],
            ['Melbourne', 'mel', 'Melbourne, Australia', 'au'],
            ['Sao Paulo', 'sao', 'Sao Paulo, Brazil', 'br'],
            ['Bangalore', 'blr', 'Bangalore, India', 'in'],
            ['Delhi NCR', 'del', 'Delhi, India', 'in'],
            ['Mumbai', 'bom', 'Mumbai, India', 'in'],
            ['Johannesburg', 'jnb', 'Johannesburg, South Africa', 'za'],
            ['Tel Aviv', 'tlv', 'Tel Aviv, Israel', 'il'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "Vultr {$r[0]}",
                "{$r[1]}-ping.vultr.com",
                'vultr',
                'Vultr',
                $r[2],
                $r[3],
                750 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function linode(): array
    {
        $regions = [
            ['Newark', 'newark', 'Newark, USA', 'us'],
            ['Atlanta', 'atlanta', 'Atlanta, USA', 'us'],
            ['Dallas', 'dallas', 'Dallas, USA', 'us'],
            ['Fremont', 'fremont', 'Fremont, USA', 'us'],
            ['Toronto', 'toronto', 'Toronto, Canada', 'ca'],
            ['London', 'london', 'London, UK', 'gb'],
            ['Frankfurt', 'frankfurt', 'Frankfurt, Germany', 'de'],
            ['Singapore', 'singapore', 'Singapore', 'sg'],
            ['Tokyo 2', 'tokyo2', 'Tokyo, Japan', 'jp'],
            ['Mumbai', 'mumbai', 'Mumbai, India', 'in'],
            ['Sydney', 'sydney', 'Sydney, Australia', 'au'],
            ['Sao Paulo', 'saopaulo', 'Sao Paulo, Brazil', 'br'],
            ['Osaka', 'osaka', 'Osaka, Japan', 'jp'],
            ['Paris', 'paris', 'Paris, France', 'fr'],
            ['Stockholm', 'stockholm', 'Stockholm, Sweden', 'se'],
            ['Seattle', 'seattle', 'Seattle, USA', 'us'],
            ['Milan', 'milan', 'Milan, Italy', 'it'],
            ['Jakarta', 'jakarta', 'Jakarta, Indonesia', 'id'],
        ];

        return array_map(
            fn ($r, $i) => $this->row(
                "Linode {$r[0]}",
                "speedtest.{$r[1]}.linode.com",
                'linode',
                'Akamai Linode',
                $r[2],
                $r[3],
                800 + $i
            ),
            $regions,
            array_keys($regions)
        );
    }

    private function ovh(): array
    {
        return [
            $this->row('OVH Gravelines', 'gra.proof.ovh.net', 'ovh', 'OVHcloud', 'Gravelines, France', 'fr', 850),
            $this->row('OVH Roubaix', 'rbx.proof.ovh.net', 'ovh', 'OVHcloud', 'Roubaix, France', 'fr', 851),
            $this->row('OVH Strasbourg', 'sbg.proof.ovh.net', 'ovh', 'OVHcloud', 'Strasbourg, France', 'fr', 852),
            $this->row('OVH Beauharnois', 'bhs.proof.ovh.net', 'ovh', 'OVHcloud', 'Beauharnois, Canada', 'ca', 853),
            $this->row('OVH Vint Hill', 'vin.proof.ovh.net', 'ovh', 'OVHcloud', 'Vint Hill, USA', 'us', 854),
            $this->row('OVH Hillsboro', 'hil.proof.ovh.net', 'ovh', 'OVHcloud', 'Hillsboro, USA', 'us', 855),
            $this->row('OVH Warsaw', 'waw.proof.ovh.net', 'ovh', 'OVHcloud', 'Warsaw, Poland', 'pl', 856),
            $this->row('OVH London', 'lon.proof.ovh.net', 'ovh', 'OVHcloud', 'London, UK', 'gb', 857),
            $this->row('OVH Frankfurt', 'fra.proof.ovh.net', 'ovh', 'OVHcloud', 'Frankfurt, Germany', 'de', 858),
            $this->row('OVH Sydney', 'syd.proof.ovh.net', 'ovh', 'OVHcloud', 'Sydney, Australia', 'au', 859),
            $this->row('OVH Singapore', 'sgp.proof.ovh.net', 'ovh', 'OVHcloud', 'Singapore', 'sg', 860),
            $this->row('OVH Mumbai', 'bom.proof.ovh.net', 'ovh', 'OVHcloud', 'Mumbai, India', 'in', 861),
        ];
    }

    private function scaleway(): array
    {
        return [
            $this->row('Scaleway Paris', 'ping-paris1.scaleway.com', 'scaleway', 'Scaleway', 'Paris, France', 'fr', 870),
            $this->row('Scaleway Amsterdam', 'ping-ams1.scaleway.com', 'scaleway', 'Scaleway', 'Amsterdam, Netherlands', 'nl', 871),
            $this->row('Scaleway Warsaw', 'ping-waw1.scaleway.com', 'scaleway', 'Scaleway', 'Warsaw, Poland', 'pl', 872),
            $this->row('Scaleway.com', 'www.scaleway.com', 'scaleway', 'Scaleway', 'France', 'fr', 873),
        ];
    }

    private function cdn(): array
    {
        return [
            $this->row('Akamai', 'www.akamai.com', 'cdn', 'Akamai', 'Global CDN', null, 900),
            $this->row('Fastly', 'www.fastly.com', 'cdn', 'Fastly', 'Global CDN', null, 901),
            $this->row('BunnyCDN', 'bunny.net', 'cdn', 'Bunny.net', 'Global CDN', null, 902),
            $this->row('KeyCDN', 'www.keycdn.com', 'cdn', 'KeyCDN', 'Global CDN', null, 903),
            $this->row('CDN77', 'www.cdn77.com', 'cdn', 'CDN77', 'Global CDN', null, 904),
            $this->row('CacheFly', 'www.cachefly.com', 'cdn', 'CacheFly', 'Global CDN', null, 905),
            $this->row('StackPath', 'www.stackpath.com', 'cdn', 'StackPath', 'Global CDN', null, 906),
            $this->row('GCore', 'gcore.com', 'cdn', 'Gcore', 'Global CDN', null, 907),
        ];
    }

    private function dns(): array
    {
        return [
            $this->row('Google DNS 8.8.8.8', '8.8.8.8', 'other', 'Google', 'Global Anycast', null, 920),
            $this->row('Google DNS 8.8.4.4', '8.8.4.4', 'other', 'Google', 'Global Anycast', null, 921),
            $this->row('Quad9 9.9.9.9', '9.9.9.9', 'other', 'Quad9', 'Global Anycast', null, 922),
            $this->row('OpenDNS', '208.67.222.222', 'other', 'Cisco OpenDNS', 'Global Anycast', null, 923),
            $this->row('AdGuard DNS', '94.140.14.14', 'other', 'AdGuard', 'Global Anycast', null, 924),
        ];
    }

    private function gamingPlatforms(): array
    {
        return [
            $this->row('Steam', 'store.steampowered.com', 'gaming_platform', 'Valve', 'Global CDN', 'us', 950),
            $this->row('Steam API', 'api.steampowered.com', 'gaming_platform', 'Valve', 'Global CDN', 'us', 951),
            $this->row('Epic Games', 'www.epicgames.com', 'gaming_platform', 'Epic Games', 'Global CDN', 'us', 952),
            $this->row('Battle.net', 'battle.net', 'gaming_platform', 'Blizzard', 'Global CDN', 'us', 953),
            $this->row('Riot Games', 'www.riotgames.com', 'gaming_platform', 'Riot Games', 'Global CDN', 'us', 954),
            $this->row('Xbox Live', 'www.xbox.com', 'gaming_platform', 'Microsoft', 'Global CDN', 'us', 955),
            $this->row('PlayStation', 'www.playstation.com', 'gaming_platform', 'Sony', 'Global CDN', 'jp', 956),
            $this->row('Nintendo', 'www.nintendo.com', 'gaming_platform', 'Nintendo', 'Global CDN', 'jp', 957),
            $this->row('Ubisoft', 'www.ubisoft.com', 'gaming_platform', 'Ubisoft', 'Global CDN', 'fr', 958),
            $this->row('EA', 'www.ea.com', 'gaming_platform', 'Electronic Arts', 'Global CDN', 'us', 959),
            $this->row('Twitch', 'www.twitch.tv', 'gaming_platform', 'Amazon / Twitch', 'Global CDN', 'us', 960),
            $this->row('Discord', 'discord.com', 'gaming_platform', 'Discord', 'Global CDN', 'us', 961),
            $this->row('FACEIT', 'www.faceit.com', 'gaming_platform', 'FACEIT', 'Global CDN', 'gb', 962),
            $this->row('ESL / ESLGaming', 'www.eslgaming.com', 'gaming_platform', 'ESL', 'Global CDN', 'de', 963),
        ];
    }

    private function gameServers(): array
    {
        return [
            $this->row('CS2 Valve EU', '162.254.192.1', 'game_server', 'Valve', 'EU West', 'nl', 1000),
            $this->row('CS2 Valve US East', '162.254.193.1', 'game_server', 'Valve', 'US East', 'us', 1001),
            $this->row('CS2 Valve US West', '162.254.199.1', 'game_server', 'Valve', 'US West', 'us', 1002),
            $this->row('Dota 2 Valve EU', '162.254.197.1', 'game_server', 'Valve', 'EU', 'nl', 1003),
            $this->row('Minecraft Hypixel', 'mc.hypixel.net', 'game_server', 'Hypixel', 'US East', 'us', 1004),
            $this->row('Minecraft Mineplex', 'mco.mineplex.com', 'game_server', 'Mineplex', 'US', 'us', 1005),
            $this->row('Minecraft Cubecraft', 'play.cubecraft.net', 'game_server', 'CubeCraft', 'EU', 'nl', 1006),
            $this->row('LoL EUW', 'euw.leagueoflegends.com', 'game_server', 'Riot Games', 'EU West', 'ie', 1007),
            $this->row('LoL NA', 'na.leagueoflegends.com', 'game_server', 'Riot Games', 'North America', 'us', 1008),
            $this->row('LoL EUNE', 'eune.leagueoflegends.com', 'game_server', 'Riot Games', 'EU Nordic & East', 'pl', 1009),
            $this->row('LoL TR', 'tr.leagueoflegends.com', 'game_server', 'Riot Games', 'Turkey', 'tr', 1010),
            $this->row('Valorant', 'playvalorant.com', 'game_server', 'Riot Games', 'Global', 'us', 1011),
            $this->row('Fortnite Epic', 'www.fortnite.com', 'game_server', 'Epic Games', 'Global', 'us', 1012),
            $this->row('PUBG', 'www.pubg.com', 'game_server', 'KRAFTON', 'Global', 'kr', 1013),
            $this->row('Apex Legends', 'apexlegends.com', 'game_server', 'EA / Respawn', 'Global', 'us', 1014),
            $this->row('Roblox', 'www.roblox.com', 'game_server', 'Roblox', 'Global CDN', 'us', 1015),
            $this->row('WoW EU', 'worldofwarcraft.blizzard.com', 'game_server', 'Blizzard', 'EU', 'fr', 1016),
            $this->row('GTA Online / Rockstar', 'www.rockstargames.com', 'game_server', 'Rockstar', 'Global CDN', 'us', 1017),
            $this->row('Warzone / Activision', 'www.callofduty.com', 'game_server', 'Activision', 'Global CDN', 'us', 1018),
            $this->row('Path of Exile', 'www.pathofexile.com', 'game_server', 'GRINDING GEAR', 'Global', 'nz', 1019),
            $this->row('Final Fantasy XIV', 'eu.finalfantasyxiv.com', 'game_server', 'Square Enix', 'EU', 'de', 1020),
            $this->row('Lost Ark', 'www.playlostark.com', 'game_server', 'Amazon Games', 'Global', 'kr', 1021),
            $this->row('New World', 'www.newworld.com', 'game_server', 'Amazon Games', 'Global', 'us', 1022),
            $this->row('OSRS', 'oldschool.runescape.com', 'game_server', 'Jagex', 'Global', 'gb', 1023),
            $this->row('FiveM', 'fivem.net', 'game_server', 'Cfx.re', 'Global', 'nl', 1024),
            $this->row('Rust', 'rust.facepunch.com', 'game_server', 'Facepunch', 'Global', 'gb', 1025),
        ];
    }
}
