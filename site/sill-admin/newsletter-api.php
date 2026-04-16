<?php
// newsletter-api.php — Infomaniak Newsletter API wrapper
// SILL SA v2 — PHP 8.2 vanilla

class InfomaniakNewsletter
{
    private string $token;
    private int $domainId;
    private const BASE = 'https://api.infomaniak.com/1/newsletters';

    public function __construct(string $token, int $domainId)
    {
        $this->token    = $token;
        $this->domainId = $domainId;
    }

    // -------------------------------------------------------------------------
    // Generic HTTP
    // -------------------------------------------------------------------------

    private function request(string $method, string $path, ?array $data = null): array
    {
        $url = self::BASE . '/' . $this->domainId . $path;

        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        switch (strtoupper($method)) {
            case 'POST':
                $opts[CURLOPT_POST] = true;
                if ($data !== null) {
                    $opts[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
            case 'PUT':
                $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if ($data !== null) {
                    $opts[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
            case 'DELETE':
                $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                if ($data !== null) {
                    $opts[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
        }

        curl_setopt_array($ch, $opts);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['result' => 'error', 'error' => ['description' => 'cURL error: ' . $error]];
        }

        $decoded = json_decode($raw, true);
        if ($decoded === null) {
            return ['result' => 'error', 'error' => ['description' => 'Invalid JSON (HTTP ' . $httpCode . ')']];
        }

        return $decoded;
    }

    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    private function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, $data);
    }

    private function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, $data);
    }

    private function delete(string $path, ?array $data = null): array
    {
        return $this->request('DELETE', $path, $data);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Extract data from API response, return empty array on error */
    public static function data(array $response): mixed
    {
        if (($response['result'] ?? '') === 'success') {
            return $response['data'] ?? [];
        }
        return null;
    }

    /** Extract error message */
    public static function error(array $response): string
    {
        return $response['error']['description']
            ?? $response['error']['message']
            ?? 'Erreur inconnue';
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard(): array
    {
        return $this->get('/dashboard');
    }

    public function dashboardCampaigns(): array
    {
        return $this->get('/dashboard/campaigns');
    }

    public function subscriberStats(): array
    {
        return $this->get('/dashboard/stats/subscribers');
    }

    public function campaignStatsMonthly(): array
    {
        return $this->get('/dashboard/stats/campaigns/monthly');
    }

    // -------------------------------------------------------------------------
    // Subscribers
    // -------------------------------------------------------------------------

    public function getSubscribers(int $page = 1, int $perPage = 50, ?string $search = null, ?int $groupId = null): array
    {
        $params = ['page' => $page, 'per_page' => $perPage];
        if ($search) {
            $params['search'] = $search;
        }
        if ($groupId) {
            $params['group'] = $groupId;
        }
        $qs = http_build_query($params);
        return $this->get('/subscribers?' . $qs);
    }

    public function getSubscriber(int $id): array
    {
        return $this->get('/subscribers/' . $id);
    }

    public function createSubscriber(string $email, array $fields = [], array $groups = []): array
    {
        $data = ['email' => $email];
        if ($fields) {
            $data['fields'] = $fields;
        }
        if ($groups) {
            $data['groups'] = $groups;
        }
        return $this->post('/subscribers', $data);
    }

    public function updateSubscriber(int $id, array $data): array
    {
        return $this->put('/subscribers/' . $id, $data);
    }

    public function deleteSubscriber(int $id): array
    {
        return $this->delete('/subscribers/' . $id);
    }

    public function countStatus(): array
    {
        return $this->get('/subscribers/count_status');
    }

    public function unsubscribe(array $subscriberIds): array
    {
        return $this->put('/subscribers/unsubscribe', ['ids' => $subscriberIds]);
    }

    public function assignSubscribers(array $subscriberIds, array $groupIds): array
    {
        return $this->put('/subscribers/assign', [
            'ids'    => $subscriberIds,
            'groups' => $groupIds,
        ]);
    }

    public function unassignSubscribers(array $subscriberIds, array $groupIds): array
    {
        return $this->put('/subscribers/unassign', [
            'ids'    => $subscriberIds,
            'groups' => $groupIds,
        ]);
    }

    // -------------------------------------------------------------------------
    // Groups
    // -------------------------------------------------------------------------

    public function getGroups(): array
    {
        return $this->get('/groups');
    }

    public function getGroup(int $id): array
    {
        return $this->get('/groups/' . $id);
    }

    public function createGroup(string $name): array
    {
        return $this->post('/groups', ['name' => $name]);
    }

    public function updateGroup(int $id, string $name): array
    {
        return $this->put('/groups/' . $id, ['name' => $name]);
    }

    public function deleteGroup(int $id): array
    {
        return $this->delete('/groups/' . $id);
    }

    public function getGroupSubscribers(int $groupId, int $page = 1, int $perPage = 50): array
    {
        $qs = http_build_query(['page' => $page, 'per_page' => $perPage]);
        return $this->get('/groups/' . $groupId . '/subscribers?' . $qs);
    }

    public function assignToGroup(int $groupId, array $subscriberIds): array
    {
        return $this->post('/groups/' . $groupId . '/subscribers/assign', ['ids' => $subscriberIds]);
    }

    public function unassignFromGroup(int $groupId, array $subscriberIds): array
    {
        return $this->post('/groups/' . $groupId . '/subscribers/unassign', ['ids' => $subscriberIds]);
    }

    // -------------------------------------------------------------------------
    // Campaigns
    // -------------------------------------------------------------------------

    public function getCampaigns(): array
    {
        return $this->get('/campaigns');
    }

    public function getCampaign(int $id): array
    {
        return $this->get('/campaigns/' . $id);
    }

    public function createCampaign(array $data): array
    {
        return $this->post('/campaigns', $data);
    }

    public function updateCampaign(int $id, array $data): array
    {
        return $this->put('/campaigns/' . $id, $data);
    }

    public function deleteCampaign(int $id): array
    {
        return $this->delete('/campaigns/' . $id);
    }

    public function sendTest(int $campaignId, string $email): array
    {
        return $this->post('/campaigns/' . $campaignId . '/test', ['email' => $email]);
    }

    public function scheduleCampaign(int $campaignId, ?int $timestamp = null): array
    {
        $data = [];
        if ($timestamp !== null) {
            $data['started_at'] = $timestamp;
        }
        return $this->put('/campaigns/' . $campaignId . '/schedule', $data);
    }

    public function cancelCampaign(int $campaignId): array
    {
        return $this->put('/campaigns/' . $campaignId . '/cancel');
    }

    public function duplicateCampaign(int $campaignId): array
    {
        return $this->post('/campaigns/' . $campaignId . '/duplicate');
    }

    // -------------------------------------------------------------------------
    // Campaign Reports
    // -------------------------------------------------------------------------

    public function campaignTracking(int $campaignId): array
    {
        return $this->get('/campaigns/' . $campaignId . '/tracking');
    }

    public function campaignLinks(int $campaignId): array
    {
        return $this->get('/campaigns/' . $campaignId . '/report/links');
    }

    public function campaignActivity(int $campaignId): array
    {
        return $this->get('/campaigns/' . $campaignId . '/report/activity');
    }

    // -------------------------------------------------------------------------
    // Templates
    // -------------------------------------------------------------------------

    public function getTemplates(): array
    {
        return $this->get('/templates');
    }

    public function getTemplateHtml(int $templateId): array
    {
        return $this->get('/templates/' . $templateId . '/html');
    }

    // -------------------------------------------------------------------------
    // Fields
    // -------------------------------------------------------------------------

    public function getFields(): array
    {
        return $this->get('/fields');
    }

    // -------------------------------------------------------------------------
    // Credits
    // -------------------------------------------------------------------------

    public function getCredits(): array
    {
        return $this->get('/credits');
    }

    public function getCreditsDetails(): array
    {
        return $this->get('/credits/details');
    }
}
