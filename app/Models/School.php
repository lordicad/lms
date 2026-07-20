<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'state'];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /** Everyone (teachers and students) attached to this school. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_STUDENT);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_TEACHER);
    }
}
