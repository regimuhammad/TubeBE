<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\arisan_participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArisanParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $participants = arisan_participant::with(['user', 'arisanGroup'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $participants
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'group_id' => 'required|exists:arisan_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $participant = arisan_participant::create([
            'user_id' => $request->user_id,
            'group_id' => $request->group_id,
            'status' => $request->status ?? 'aktif',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Peserta berhasil ditambahkan',
            'data' => $participant
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
