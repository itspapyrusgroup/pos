<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(config('rbac.permissions', []));

        foreach ($permissions as $item) {
            Permission::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'modul' => $item['modul'],
                    'aksi' => $item['aksi'],
                    'label' => $item['label'],
                ]
            );
        }

        $superAdmin = Role::updateOrCreate(
            ['nama' => 'Super Admin'],
            ['deskripsi' => 'Akses penuh ke seluruh modul', 'status' => true]
        );

        $superAdmin->permissions()->sync(Permission::pluck('id'));

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@papyrus.local'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password123'),
                'role_id' => $superAdmin->id,
                'status' => true,
            ]
        );

        if ($adminUser->role_id !== $superAdmin->id) {
            $adminUser->update(['role_id' => $superAdmin->id]);
        }
    }
}
