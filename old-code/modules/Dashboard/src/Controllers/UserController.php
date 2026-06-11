<?php

namespace Modules\Dashboard\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

/**
 * Class UserController
 * 
 * Handles User Management within the Dashboard module.
 */
class UserController extends AdminController
{
    protected DatabaseProviderInterface $db;

    public function __construct(DatabaseProviderInterface $db)
    {
        $this->db = $db;
    }

    /**
     * GET /dashboard/users
     */
    public function index(): Response
    {
        $users = $this->db->database()->select('*')
            ->from('users')
            ->run()
            ->fetchAll();

        return $this->adminPage('User Management', view('admin/user/index', [
            'users' => $users
        ]));
    }
}
