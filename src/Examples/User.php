<?php

namespace onamfc\EloquentJsonSchema\Examples;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use onamfc\EloquentJsonSchema\Attributes as S;

#[S\SchemaName('User')]
#[S\RequestOnly(['password'])]
class User extends Model
{
    protected $fillable = [
        'email',
        'name',
        'plan',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    #[S\Title('User ID')]
    #[S\Format('uuid')]
    public $id;

    #[S\Description('Primary email address')]
    #[S\Format('email')]
    public $email;

    #[S\Description('Full name of the user')]
    public $name;

    #[S\Description('Subscription plan')]
    #[S\Enum(['basic', 'pro', 'enterprise'])]
    public $plan;

    #[S\Description('User password')]
    #[S\Format('password')]
    public $password;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function rules(string $schemaType = 'request'): array
    {
        $rules = [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|min:2|max:255',
            'plan' => 'required|in:basic,pro,enterprise',
        ];

        if ($schemaType === 'request') {
            $rules['password'] = 'required|string|min:8';
        }

        return $rules;
    }
}