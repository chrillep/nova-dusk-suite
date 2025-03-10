<?php

namespace App\Nova;

use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MultiSelect;
use Laravel\Nova\Fields\Timezone;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @template TModel of \App\Models\Profile
 * @extends \App\Nova\Resource<TModel>
 */
class Profile extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<TModel>
     */
    public static $model = \App\Models\Profile::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            BelongsTo::make('User'),

            URL::make('GitHub URL')->filterable(function ($request, $query, $value, $attribute) {
                $query->where($attribute, '=', 'https://github.com/'.$value);
            }),
            URL::make('Twitter URL'),

            Timezone::make('Timezone')
                    ->nullable()
                    ->rules(['nullable', Rule::in(timezone_identifiers_list())])
                    ->filterable()
                    ->searchable(file_exists(base_path('.searchable'))),

            MultiSelect::make('Interests')->options([
                'laravel' => ['label' => 'Laravel', 'group' => 'PHP'],
                'phpunit' => ['label' => 'PHPUnit', 'group' => 'PHP'],
                'livewire' => ['label' => 'Livewire', 'group' => 'PHP'],
                'swoole' => ['label' => 'Swoole', 'group' => 'PHP'],
                'react' => ['label' => 'React', 'group' => 'JavaScript'],
                'vue' => ['label' => 'Vue', 'group' => 'JavaScript'],
                'hack' => ['label' => 'Hack'],
            ])->filterable(),

            HasOne::ofMany('Latest Post', 'latestPost', Post::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }

    /**
     * Return the location to redirect the user after creation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  static  $resource
     * @return string
     */
    public static function redirectAfterCreate(NovaRequest $request, $resource)
    {
        if ($request->viaResource === 'users' && $request->viaRelationship === 'profile') {
            return '/resources/users/'.$resource->user_id;
        }

        return parent::redirectAfterCreate($request, $resource);
    }
}
