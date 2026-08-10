<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        if(!$user->canCreateProject()){
            return response()->json([
                'message'=>'Unauthorized to create project'
                ],403);
        }
        // Logic to store a new project
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'goal_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:active,completed,cancelled',
            // 'images' => 'required|array',
            // 'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]); 

        $project = Project::create([
            'title'=>$request->title,
            'description'=>$request->description,
            'goal_amount'=>$request->goal_amount,
            'organization_id'=>$user->organization->id,
            'status'=>$request->status??'active',
        
            // 'current_amount'=>0,
          
            ]);
        
        
        // if($request->has('images')){
        //     foreach($request->file('images') as $image){
        //         $path = $image->store('project_images', 'public');
        //         $project->images()->create(['image_path'=>$path]);
        //     }
        // }
        
        // انشاء محفظة للمشروع
        if($project->isActive()){
            $project->wallet()->create([
                'balance'=>0,
            ]);
        }

        return response()->json([
            'message'=>'Project created successfully',
            'project'=>$project->only('id','title','description','goal_amount','status'),
            'images'=>$project->images->pluck('image_path'),
            'wallet'=>$project->wallet()->first()
        ],
            201);
    }
    public function show($id)
{
    $project = Project::findOrFail($id);
    $currentAmount = $project->wallet->balance ?? 0;

    return response()->json([
        'project' => [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'goal_amount' => $project->goal_amount,
            'donated_amount' => $currentAmount, // 💡 مهم جداً لشاشة فلاتر لكي لا تظهر $0
            'remaining_amount' => max(0, $project->goal_amount - $currentAmount),
            'status' => $project->status,
        ],
    ]);
}           
    public function getProjectsForOrganization(Request $request, $id)
    {
        // 1. جلب مشاريع الجمعية بناءً على الـ id المبعوث في كل الأحوال
        $projects = Project::with(['images', 'wallet'])
            ->where('organization_id', $id)
            ->get();

        // 2. عمل الحسابات المالية الموحدة (التي تظهر للمدير والمتبرع على حد سواء)
        $projects->each(function ($project) {
            $donated = $project->wallet->balance ?? 0;
            $project->donated_amount = $donated;
            $project->remaining_amount = max(0, $project->goal_amount - $donated);
        });

        return response()->json($projects);
    }

    public function getAllProjects()
    {
        // جلب كل المشاريع في النظام مع الصور والمحفظة والجمعية التابع لها المشروع
        $projects = Project::with(['images', 'wallet', 'organization'])->get();

        // حساب المبالغ المالية لكل مشروع في النظام
        $projects->each(function ($project) {
            $donated = $project->wallet->balance ?? 0;
            $project->donated_amount = $donated;
            $project->remaining_amount = max(0, $project->goal_amount - $donated);
        });

        return response()->json($projects);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $user = $request->user();

        // الأمن: التأكد من أن المدير يملك هذا المشروع عبر الجمعية الخاصة به
        if (!$user->isOrganization() || $project->organization_id !== $user->organization->id) {
            return response()->json(['message' => 'Unauthorized to update this project'], 403);
        }

        // التحقق من المدخلات الجديدة
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'goal_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        // تحديث البيانات في قاعدة البيانات
        $project->update([
            'title' => $request->title,
            'description' => $request->description,
            'goal_amount' => $request->goal_amount,
            'status' => $request->status ?? $project->status,
        ]);

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->only('id', 'title', 'description', 'goal_amount', 'status')
        ], 200);
    }
    public function delete(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $user = $request->user();

        // الأمن: التأكد من أن المدير يملك هذا المشروع عبر الجمعية الخاصة به
        if (!$user->isOrganization() || $project->organization_id !== $user->organization->id) {
            return response()->json(['message' => 'Unauthorized to delete this project'], 403);
        }

        // حذف المشروع
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully'
        ], 200);
    }
    // public function destroy(Project $project)
    // {
    //     // Logic to delete a project
    //     $project->delete();
    //     return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');   
    // }

    // public function create()
    // {
    //     // Logic to show form for creating a new project
    //     return view('projects.create');
    // }
}
