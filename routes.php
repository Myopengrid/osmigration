<?php

Route::get(ADM_URI.'/(:bundle)', function()
{
    return Controller::call('osmigration::backend.osmigration@index');
});

Route::post(ADM_URI.'/(:bundle)', function()
{
    return Controller::call('osmigration::backend.osmigration@create');
});