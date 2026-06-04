<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseStructureReorderRequest;
use App\Models\Course;
use App\Services\CourseStructureReorderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CourseStructureReorderController extends Controller
{
    public function __invoke(
        CourseStructureReorderRequest $request,
        int|string $courseId,
        CourseStructureReorderService $reorder
    ): JsonResponse {
        $course = Course::findOrFail($courseId);

        Log::info('Admin CourseStructureReorder hit', [
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
