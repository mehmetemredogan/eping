<?php

return [
    'nav_terms' => 'Terms of use',
    'nav_privacy' => 'Privacy (KVKK / GDPR)',
    'nav_cookies' => 'Cookies',

    'terms_title' => 'Membership Agreement and Terms of Use',
    'terms_updated' => 'Last updated: 25 July 2026',
    'terms_intro' => 'These terms govern the ePing web application and terminal client. By creating an account or using the service, you agree to them.',

    'terms_s1_title' => '1. Scope of the service',
    'terms_s1_body' => 'ePing provides network latency measurement and traceroute analysis toward cloud providers, game servers, and CDN targets. Measurements run on your device via the terminal client, not in the browser. The web UI covers membership, result history, anonymized statistics, and administration.',

    'terms_s2_title' => '2. Account and membership',
    'terms_s2_body' => 'Registration requires only a username and password; email or legal name is not required. You are responsible for account security. Password recovery by email may be unavailable. Accounts may be suspended or deleted for abuse or service disruption.',

    'terms_s3_title' => '3. What information do we store?',
    'terms_s3_intro' => 'We may store the following data to operate the service:',
    'terms_s3_items' => [
        'Account: username, password hash, admin flag, timestamps.',
        'Session and API: web session cookies, Sanctum API tokens (for client login).',
        'Measurement results: target, status, latency/jitter/loss metrics, sample values, traceroute hops and raw output summary, client version, connection type (Wi‑Fi / Ethernet / unknown).',
        'Network context: client IP address; coarse location / ASN / ISP derived from IP (via a third-party IP geolocation service); DNS resolution records for targets.',
        'Technical logs: limited server logs for security and troubleshooting.',
    ],

    'terms_s4_title' => '4. How are records collected?',
    'terms_s4_body' => 'Measurement records are created when a signed-in user’s terminal client posts results to the API. The website does not run ping tests. The client measures HTTP latency and optional traceroute, then uploads the result linked to your account. Public statistics aggregate these records anonymously (without username or raw IP, and only above a sample threshold).',

    'terms_s5_title' => '5. Client use — your responsibility',
    'terms_s5_body' => 'Downloading, installing, and running the terminal client is entirely your responsibility. You must use it in line with your network rules, employer policies, and ISP terms. Unauthorized scanning, abuse, or illegal activity is prohibited. ePing is not liable for misconfiguration, misinterpreted results, outages, or third-party systems. The software is provided “as is” without warranty of uninterrupted or error-free operation.',

    'terms_s6_title' => '6. Acceptable use',
    'terms_s6_body' => 'Use the service only for legitimate network measurement and performance evaluation. Overloading the API, interfering with others, exploiting vulnerabilities, or injecting misleading data is forbidden.',

    'terms_s7_title' => '7. Intellectual property and license',
    'terms_s7_body' => 'ePing software is subject to the project’s open-source license. Branding, UI copy, and hosted service content remain with their respective owners unless stated otherwise.',

    'terms_s8_title' => '8. Changes',
    'terms_s8_body' => 'These terms may be updated from time to time. Material changes update the “last updated” date on this page. Continued use after an update means you accept the revised terms.',

    'terms_s9_title' => '9. Contact',
    'terms_s9_body' => 'For questions, use the project repository or contact channels listed on the site: github.com/mehmetemredogan/eping',

    'privacy_title' => 'Privacy Notice (KVKK and GDPR)',
    'privacy_updated' => 'Last updated: 25 July 2026',
    'privacy_intro' => 'This notice explains personal data processing under Turkey’s KVKK (Law No. 6698) and the EU GDPR. ePing is designed to collect as little personal data as practical (email and legal name are not required).',

    'privacy_s1_title' => '1. Controller',
    'privacy_s1_body' => 'The controller is the operator hosting this site and related API. Use the project contact channels for requests.',

    'privacy_s2_title' => '2. Data we process',
    'privacy_s2_body' => 'Username and password hash; measurement results and traceroute details; client IP and coarse ISP/ASN/country derived from it; session and API tokens; functional cookies such as locale. We do not collect email, phone, or national ID numbers.',

    'privacy_s3_title' => '3. Purposes and legal bases',
    'privacy_s3_items' => [
        'Providing the service and managing your account (contract / service delivery).',
        'Showing measurement history and authenticating the API.',
        'Producing anonymized / aggregated ISP statistics (legitimate interest; k-anonymity thresholds apply).',
        'Security, abuse prevention, and technical logging (legitimate interest / legal obligation).',
        'Essential cookies for locale and session (service delivery).',
    ],

    'privacy_s4_title' => '4. Transfers and third parties',
    'privacy_s4_body' => 'A third-party IP geolocation service may be used for approximate network context. Hosting providers may process data as processors. Data may be stored where the service or infrastructure is hosted. We do not sell data for marketing.',

    'privacy_s5_title' => '5. Retention',
    'privacy_s5_body' => 'Account data until deletion; measurement results until you clear history or the account is closed; technical logs for a reasonable period; sessions/tokens until expiry or revocation.',

    'privacy_s6_title' => '6. Your rights (KVKK / GDPR)',
    'privacy_s6_body' => 'Under KVKK Art. 11 and the GDPR you may request access, rectification, erasure, restriction, objection, and (where applicable) portability. In the member panel you can clear history and update your username. Contact us for further requests. You may lodge a complaint with Turkey’s Personal Data Protection Authority or your local supervisory authority.',

    'privacy_s7_title' => '7. Security',
    'privacy_s7_body' => 'Passwords are stored hashed; API access uses tokens. No system is perfectly secure—use a strong password and do not share tokens.',

    'cookies_title' => 'Cookie Policy',
    'cookies_updated' => 'Last updated: 25 July 2026',
    'cookies_intro' => 'ePing uses essential technical cookies required to run the site. We do not use advertising or third-party tracking cookies.',

    'cookies_s1_title' => 'Cookies we use',
    'cookies_s1_items' => [
        'Session cookie — to keep you signed in (essential).',
        'XSRF-TOKEN / CSRF — form security (essential).',
        'Locale preference — to remember UI language (functional).',
        'Cookie notice acknowledgement — stored only in your browser (localStorage); not sent to the server.',
    ],

    'cookies_s2_title' => 'Managing cookies',
    'cookies_s2_body' => 'You can delete or block cookies in your browser settings. Blocking essential cookies may break login and forms. Choosing “Got it” on the notice stores your preference on this device.',

    'banner_text' => 'This site uses essential cookies for session, security, and language preference. See the cookie policy for details.',
    'banner_accept' => 'Got it',
    'banner_learn' => 'Cookie policy',

    'register_accept_label' => 'I have read and accept the :terms and :privacy. I understand that use of the client is my own responsibility.',
    'register_accept_terms' => 'Membership Agreement and Terms of Use',
    'register_accept_privacy' => 'Privacy Notice',
    'register_accept_required' => 'You must accept the membership agreement and privacy notice to continue.',
];
