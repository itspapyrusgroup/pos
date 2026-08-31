<?php

use App\Models\Role;
use App\Models\User;

test('super admin has all permissions even when pivot sync is missing', function () {
    $role = new Role([
        'nama' => 'Super Admin',
        'deskripsi' => 'Akses penuh ke seluruh modul',
        'status' => true,
    ]);

    $user = new User();
    $user->setRelation('role', $role);

    expect($user->hasPermission('pos.input_order.read'))->toBeTrue();
    expect($user->hasPermission('permission.that.does.not.exist'))->toBeTrue();
});

test('non super admin still requires explicit permission', function () {
    $role = new Role([
        'nama' => 'Kasir',
        'deskripsi' => 'Role kasir',
        'status' => true,
    ]);
    $role->setRelation('permissions', collect());

    $user = new User();
    $user->setRelation('role', $role);

    expect($user->hasPermission('pos.input_order.read'))->toBeFalse();
});
