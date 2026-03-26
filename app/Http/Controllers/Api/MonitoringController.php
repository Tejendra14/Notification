<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationFilterRequest;
use App\Repositories\NotificationRepository;
use Illuminate\Http\JsonResponse;

class MonitoringController extends Controller
{
    public function __construct(
        private NotificationRepository $repository
    ) {}
    
    public function recent(NotificationFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $request->get('per_page', 20);
        
        $notifications = $this->repository->getRecent($filters, $perPage);
        
        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }
    
    public function summary(?string $tenantId = null): JsonResponse
    {
        $summary = $this->repository->getSummary($tenantId);
        
        return response()->json($summary);
    }
}