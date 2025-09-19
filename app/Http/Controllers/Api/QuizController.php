<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * Get questions for an episode
     */
    public function getEpisodeQuestions(int $episodeId): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->quizService->getEpisodeQuestions($episodeId, $userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Submit quiz answer
     */
    public function submitAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|integer|exists:episode_questions,id',
            'selected_answer' => 'required|string|in:a,b,c,d',
        ]);

        $userId = Auth::id();
        $result = $this->quizService->submitAnswer(
            $userId,
            $request->question_id,
            $request->selected_answer
        );
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's quiz statistics
     */
    public function getUserStatistics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $days = $request->get('days', 30);
        
        $result = $this->quizService->getUserQuizStatistics($userId, $days);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get quiz statistics for an episode
     */
    public function getEpisodeStatistics(int $episodeId): JsonResponse
    {
        $result = $this->quizService->getEpisodeQuizStatistics($episodeId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global quiz statistics (Admin only)
     */
    public function getGlobalStatistics(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $days = $request->get('days', 30);
        $result = $this->quizService->getGlobalQuizStatistics($days);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Create a new question (Admin only)
     */
    public function createQuestion(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $request->validate([
            'episode_id' => 'required|integer|exists:episodes,id',
            'question' => 'required|string|max:1000',
            'option_a' => 'required|string|max:255',
            'option_b' => 'required|string|max:255',
            'option_c' => 'required|string|max:255',
            'option_d' => 'required|string|max:255',
            'correct_answer' => 'required|string|in:a,b,c,d',
            'explanation' => 'nullable|string|max:1000',
            'coins_reward' => 'nullable|integer|min:1|max:100',
            'difficulty_level' => 'nullable|integer|min:1|max:5',
            'is_active' => 'nullable|boolean',
        ]);

        $result = $this->quizService->createQuestion($request->episode_id, $request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Update an existing question (Admin only)
     */
    public function updateQuestion(Request $request, int $questionId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $request->validate([
            'question' => 'nullable|string|max:1000',
            'option_a' => 'nullable|string|max:255',
            'option_b' => 'nullable|string|max:255',
            'option_c' => 'nullable|string|max:255',
            'option_d' => 'nullable|string|max:255',
            'correct_answer' => 'nullable|string|in:a,b,c,d',
            'explanation' => 'nullable|string|max:1000',
            'coins_reward' => 'nullable|integer|min:1|max:100',
            'difficulty_level' => 'nullable|integer|min:1|max:5',
            'is_active' => 'nullable|boolean',
        ]);

        $result = $this->quizService->updateQuestion($questionId, $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Delete a question (Admin only)
     */
    public function deleteQuestion(int $questionId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->quizService->deleteQuestion($questionId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
