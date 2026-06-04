<?php

namespace App\Http\Controllers;

use App\Models\CourseShareLink;
use App\Services\Docs\DocsAccessService;
use App\Services\Docs\DocsReaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShareLinkController extends Controller
{
    public function __construct(
        protected DocsAccessService $access,
        protected DocsReaderService $reader
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $link = CourseShareLink::query()
            ->with('course')
            ->where('token', $token)
            ->first();

        if (! $link) {
            abort(404);
        }

        if (! $link->isValid()) {
            return view('docs.share-expired');
        }

        $course = $link->course;
        $this->access->grantShareAccess($course, $token);

        $first = $this->reader->firstLesson($course);

        if ($first) {
            return redirect()->route('docs.lesson', [$course->slug, $first->slug]);
        }

        return redirect()->route('docs.course', $course->slug);
    }
}
