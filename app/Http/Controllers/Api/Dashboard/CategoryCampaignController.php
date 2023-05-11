<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\CategoryCampaign;
use App\Events\EventNotification;

class CategoryCampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *@author puji ermanto<pujiermanto@gmail.com>
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(function ($request, $next) {
            if (Gate::allows('category-campaigns-management')) return $next($request);
            return response()->json([
                'error' => true,
                'message' => 'Anda tidak memiliki cukup hak akses'
            ]);
        });
    }

    public function index()
    {
        try {
            $category_campaigns = CategoryCampaign::whereNull('deleted_at')
                ->paginate(10);
            return response()->json([
                'message' => 'List category campaigns',
                'data' => $category_campaigns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_ready = CategoryCampaign::whereName($request->name)->get();

            if (count($check_ready) > 0) {
                return response()->json([
                    'message' => "{$request->name}, its already taken!"
                ]);
            }

            $new_category = new CategoryCampaign();
            $new_category->name = $request->name;
            $new_category->slug = Str::slug($request->name);
            $new_category->save();

            return response()->json([
                'message' => 'added new category campaign successfully',
                'data' => $new_category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $delete_category = CategoryCampaign::findOrFail($id);
            $delete_category->delete();
            $data_event = [
                'notif' => "{$delete_category->name}, success move to trash, please check trash!",
                'data' => $delete_category
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Category Campaign {$delete_category->name} success move to trash, please check trash",
                'data' => $delete_category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
