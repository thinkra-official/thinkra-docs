<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseStructureReorderRequest;
use App\Models\Course;
use App\Services\CourseAccessService;
use App\Services\CourseStructureReorderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CourseStructureReorderController extends Controller
{
    public function __construct(
        protected CourseAccessService $access
    ) {}

    public function __invoke(
        CourseStructureReorderRequest $request,
        int|string $courseId,
        CourseStructureReorderService $reorder
    ): JsonResponse {
        $course = Course::findOrFail($courseId);
        $user = auth()->user();

        if (! $this->access->canAccessCourse($user, $course)) {
            abort(404);
        }

        if (! $this->access->canManageCourse($user, $course) && ! $this->access->canEditContent($user, $course)) {
            abort(403);
        }

        Log::info('Teacher CourseStructureReorder hit', [
            'courseId' => $course->id,
            'payload' => $request->all(),
        ]);

        $reorder->reorder($course, $request->validated());

        return response()->json([
            'ok' => true,
            'message' => 'تم حفظ ترتيب المحتوى.',
        ]);
    }
}
