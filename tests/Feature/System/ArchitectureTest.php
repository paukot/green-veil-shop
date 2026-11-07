<?php

arch('app does not leave debugging statements')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch('controllers extend base controller')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

arch('models extend eloquent model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');
