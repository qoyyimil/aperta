<?php

namespace App\Features\Team\Filament;

use App\Filament\ManageSimpleRecords;

class ManageUsers extends ManageSimpleRecords
{
    protected static string $resource = UserResource::class;
}
