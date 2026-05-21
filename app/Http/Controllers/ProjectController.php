<?php

namespace App\Http\Controllers;

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
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]); 

        $project = Project::create([
            'title'=>$request->title,
            'description'=>$request->description,
            'goal_amount'=>$request->goal_amount,
            'organization_id'=>$user->organization->id,
            'status'=>$request->status??'active',
        
            // 'current_amount'=>0,
          
            ]);
        
        
        if($request->has('images')){
            foreach($request->file('images') as $image){
                $path = $image->store('project_images', 'public');
                $project->images()->create(['image_path'=>$path]);
            }
        }
        
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
    public function show(Project $project)
    {
        // Logic to show a single project
        $currentAmount = $project->wallet->balance ?? 0;
        // تحديث حالة المشروع إذا تم الوصول إلى الهدف  المفروض في التبرع انديره
        // if($project->goal_amount==$currentAmount){
        //     $project->update(['status'=>'completed']);
        // }
        return response()->json([
            'project'=>[
                'id'=>$project->id,
                'title'=>$project->title,
                'description'=>$project->description,
                'goal_amount'=>$project->goal_amount,
                'current_amount'=>$currentAmount,
                'remaining_amount'=>max(0, $project->goal_amount - $currentAmount),
                'status'=>$project->status,
            ],
        ]
        );
        
    }           
    public function getProjects()
    {
        // Logic to list all projects
        $projects = Project::with('images')->get();
        return response()->json($projects);
    }
    // public function update(Request $request, Project $project)
    // {
    //     // Logic to update a project
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'organization_id' => 'required|exists:organizations,id',
    //     ]);

    //     $project->update($request->only('name', 'description', 'organization_id'));

    //     return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    // }
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
