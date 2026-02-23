<?php

declare(strict_types=1);

namespace Modules\Profile\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class ProfileAdminController extends AdminController
{
    /** GET /dashboard/profile — current user's profile */
    public function show(Request $request): Response
    {
        // In a real app, get $userId from auth session
        $userId = (int)($request->query()['user_id'] ?? 1);
        $profile = $this->db()->select('*')->from('optilarity_profiles')->where('user_id', $userId)->run()->fetch();
        $form    = $this->renderForm($profile ? (array)$profile : ['user_id' => $userId], '/dashboard/profile', 'POST');

        return $this->htmlResponse(
            $this->adminPage('My Profile', $form, ['breadcrumbs' => ['Profile' => '']])
        );
    }

    /** POST /dashboard/profile — create or update */
    public function update(Request $request): Response
    {
        $body   = (array)$request->post();
        $userId = (int)($body['user_id'] ?? 1);

        try {
            $exists = $this->db()->select('id')->from('optilarity_profiles')->where('user_id', $userId)->run()->fetch();
            $data   = [
                'display_name' => $body['display_name'] ?? null,
                'bio'          => $body['bio']          ?? null,
                'website'      => $body['website']      ?? null,
                'timezone'     => $body['timezone']     ?? null,
                'locale'       => $body['locale']       ?? 'en',
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if ($exists) {
                $this->db()->update('optilarity_profiles', $data, ['user_id' => $userId]);
            } else {
                $this->db()->insert('optilarity_profiles')->values(array_merge($data, [
                    'user_id'    => $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]))->run();
            }
            return $this->redirect('/dashboard/profile?saved=1');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('My Profile', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/profile')));
        }
    }

    private function renderForm(array $data = [], string $action = '/dashboard/profile', string $method = 'POST'): string
    {
        $saved   = isset($_GET['saved']) ? $this->notice('Profile saved successfully!', 'success') : '';
        $timezones   = ['UTC' => 'UTC', 'Asia/Ho_Chi_Minh' => 'Asia/Ho_Chi_Minh (ICT +7)', 'America/New_York' => 'US Eastern', 'Europe/London' => 'Europe/London'];
        $localeOpts  = ['en' => 'English', 'vi' => 'Vietnamese', 'fr' => 'French', 'de' => 'German'];

        $fields = "<input type='hidden' name='user_id' value='" . ($data['user_id'] ?? 1) . "'>"
                . $this->fieldGroup('Display Name',  $this->input('display_name', 'text', $data['display_name'] ?? ''))
                . $this->fieldGroup('Bio',           $this->textarea('bio', $data['bio'] ?? '', 'Tell us about yourself…'))
                . $this->fieldGroup('Website',       $this->input('website', 'url', $data['website'] ?? ''))
                . $this->fieldGroup('Timezone',      $this->select('timezone', $timezones, $data['timezone'] ?? 'UTC'))
                . $this->fieldGroup('Locale',        $this->select('locale', $localeOpts, $data['locale'] ?? 'en'))
                . $this->submitBar('Save Profile');

        return $saved . $this->formCard('Profile Information', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
