<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('category_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $category = Category::OrderBy('id','DESC')->get();
        return view('admin.category.index', compact('category'));
    }

    public function create()
    {
        abort_if(Gate::denies('category_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('category_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|boolean',
        ]);

        try {
            if ($request->hasFile('image')) {
                $validatedData['image'] = (new AppHelper)->saveImage($request->file('image'));
            }

            Category::create($validatedData);

            return redirect()
                ->route('category.index')
                ->with('success', __('Category has been added successfully.'));
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Error creating category: ') . $e->getMessage());
        }
    }

    public function edit(Category $category)
    {
        abort_if(Gate::denies('category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('admin.category.edit', compact( 'category'));
    }

    public function update(Request $request, Category $category)
    {
        abort_if(Gate::denies('category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|boolean',
        ]);

        try {
            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($category->image) {
                    (new AppHelper)->deleteFile($category->image);
                }
                $validatedData['image'] = (new AppHelper)->saveImage($request->file('image'));
            }

            $category->update($validatedData);

            return redirect()
                ->route('category.index')
                ->with('success', __('Category has been updated successfully.'));
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Error updating category: ') . $e->getMessage());
        }
    }

}
