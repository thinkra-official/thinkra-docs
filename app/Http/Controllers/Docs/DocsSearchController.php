<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\Docs\DocsSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocsSearchController extends Controller
{
    public function __construct(
        protected DocsSearchService $search
    ) {}

    public function index(Request $request): View
    {
        $q = (string) $request->query('q', '');
        $results = $q !== '' ? $this->search->search($q) : collect();

        return view('docs.search', [
            'query' => $q,
            'results' => $results,
        ]);
    }
}
