<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ViewComposer
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function compose(View $view): void
    {
        if (! isset($view->errors)) {
            $view->with('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));
        }
    }
}
