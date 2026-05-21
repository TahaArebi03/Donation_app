<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    // public function index()
    // {
    //     // Logic to list all organizations
    //     $organizations = Organization::all();
    //     return response()->json($organizations);

    // }
    // public function create(Request $request)
    // {
    //     // Logic to show form for creating a new organization
        
    // }
    public function store(Request $request)
    {
        // Logic to store a new organization
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $organization = Organization::create($request->only('name', 'type', 'status', 'description'));

        return response()->json(['message' => 'Organization created successfully.', 'organization' => $organization], 201);
    }
    public function show(Organization $organization)
    {        // Logic to show a specific organization
        return response()->json($organization);
    }
    public function edit(Organization $organization)
    {
        // Logic to show form for editing an organization
        return view('organizations.edit', compact('organization'));
    }
    public function update(Request $request, Organization $organization)
    {
        // Logic to update an organization
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);     

        $organization->update($request->only('name', 'description'));
        return redirect()->route('organizations.index')->with('success', 'Organization updated successfully.');
    }
    public function destroy(Organization $organization)
    {
        // Logic to delete an organization
        $organization->delete();
        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully.');
    }


    
}
