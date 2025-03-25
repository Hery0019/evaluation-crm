<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class GraphController extends Controller {
    public function getGraphData()
    {
        // 🔹 Nombre de leads par mois
        $leadsByMonth = Lead::query()
            ->selectRaw('YEAR(deadline) as year, MONTH(deadline) as month, COUNT(*) as lead_count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // 🔹 Nombre de tâches par mois
        $tasksByMonth = Task::query()
            ->selectRaw('YEAR(deadline) as year, MONTH(deadline) as month, COUNT(*) as task_count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // 🔹 Nombre d'utilisateurs par département
        $usersByDepartment = Department::select('departments.id', 'departments.name', DB::raw('COUNT(department_user.user_id) as user_count'))
            ->leftJoin('department_user', 'departments.id', '=', 'department_user.department_id')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('user_count')
            ->get();

        // 🔹 Retourner tout dans un seul JSON
        return response()->json([
            'leads' => $leadsByMonth,
            'tasks' => $tasksByMonth,
            'departments' => $usersByDepartment,
        ]);
    }
}
