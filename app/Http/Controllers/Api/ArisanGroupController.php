<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\arisan_group;
use App\Models\arisan_participant;
// use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArisanGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = arisan_group::query()->withCount('participants');;

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $arisanGroups = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $arisanGroups->items(),
            'meta' => [
                'current_page' => $arisanGroups->currentPage(),
                'last_page' => $arisanGroups->lastPage(),
                'per_page' => $arisanGroups->perPage(),
                'total' => $arisanGroups->total()
            ]
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'amount' => 'required|integer',
            'duration' => 'required|string',
            'start_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }
        

        $arisanGroups = new arisan_group();
        $arisanGroups->name = $request->name;
        $arisanGroups->code = hexdec(uniqid());
        $arisanGroups->amount = $request->amount;
        $arisanGroups->duration = $request->duration;
        $arisanGroups->start_date = $request->start_date;
        $arisanGroups->end_date = $request->end_date;
        $arisanGroups->user_id = Auth::user()->id;
        $arisanGroups->current_drawer_user_id = Auth::user()->id;
        $arisanGroups->save();

        arisan_participant::create([
        'user_id' => Auth::user()->id,
        'group_id' => $arisanGroups->id,
    ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Arisan Group created successfully',
            'data' => $arisanGroups
        ], 201);
    }

    public function joinById($groupId, Request $request)
    {
        $group = arisan_group::findOrFail($groupId);

        $alreadyJoined = arisan_participant::where('user_id', $request->user()->id)
            ->where('group_id', $group->id)
            ->exists();

        if ($alreadyJoined) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu sudah join ke grup ini.',
            ], 409);
        }

        arisan_participant::create([
            'user_id' => $request->user()->id,
            'group_id' => $group->id,
            'joined' => 1,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil join grup arisan',
            'group' => $group,
        ]);
    }


    public function saveContractAddress(Request $request, $id)
    {
        $request->validate([
            'contract_address' => 'required|string',
        ]);

        $group = arisan_group::findOrFail($id);

        // Otorisasi: hanya peserta yang terdaftar yang boleh update
        if (!$group->users->contains($request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $group->contract_address = $request->contract_address;
        $group->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Contract address saved',
            'group' => $group,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'status' => 'success',
            'data' => $id
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, arisan_group $arisanGroup)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'amount' => 'required|integer',
            'duration' => 'required|string',
            'start_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) {
            $arisanGroup->name = $request->name;
        }

        if ($request->has('amount')) {
            $arisanGroup->amount = $request->amount;
        }

        if ($request->has('duration')) {
            $arisanGroup->duration = $request->duration;
        }

        if ($request->has('start_date')) {
            $arisanGroup->start_date = $request->start_date;
        }

        $arisanGroup->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $arisanGroup
        ], 200);


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(arisan_group $arisanGroup)
    {
        $arisanGroup->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully'
        ], 200);
    }

    public function viewArisan(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = arisan_group::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $arisan = $query->paginate($perPage);

        return response()->json($arisan, 200);
    }


    public function myGroups(Request $request)
    {
        $user = auth()->user();

        $groups = DB::table('arisan_participants')
            ->join('arisan_groups', 'arisan_participants.group_id', '=', 'arisan_groups.id')
            ->join('users as drawer', 'arisan_groups.current_drawer_user_id', '=', 'drawer.id')
            ->join('users as participant', 'arisan_participants.user_id', '=', 'participant.id')
            ->where('arisan_participants.user_id', $user->id)
            ->select(
                'arisan_groups.*',
                'arisan_participants.has_paid as has_paid',
                'drawer.wallet_address as current_drawer',
                'participant.wallet_address as user_wallet'
            )
            ->paginate(10);


        return response()->json([
            'status' => 'success',
            'data' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ]
        ]);
    }


    public function pay($groupId)
    {
        $userId = auth()->id();

        $participant = DB::table('arisan_participants')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();

        if (!$participant) {
            return response()->json(['message' => 'Kamu belum join grup ini'], 404);
        }

        if ($participant->has_paid) {
            return response()->json(['message' => 'Kamu sudah membayar arisan.'], 400);
        }

        DB::table('arisan_participants')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->update(['has_paid' => true]);

        return response()->json(['message' => 'Pembayaran berhasil']);
    }


    public function recordDraw(Request $request, $groupId)
    {
        $request->validate([
            'winner_id' => 'required|exists:users,id',
            'draw_number' => 'required|integer',
            'draw_date' => 'required|date',
        ]);

        // hitung peserta & jumlah draw
        $participants = DB::table('arisan_participants')->where('group_id', $groupId)->count();
        $totalDraws = DB::table('arisan_draws')->where('group_id', $groupId)->count();

        if ($totalDraws >= $participants) {
            return response()->json(['message' => 'Arisan sudah selesai'], 400);
        }

        DB::table('arisan_draws')->insert([
            'group_id' => $groupId,
            'draw_number' => $request->draw_number,
            'winner_id' => $request->winner_id,
            'draw_date' => $request->draw_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Hasil draw berhasil dicatat']);
    }


    public function getNextDrawNumber($groupId)
    {
        $lastDraw = DB::table('arisan_draws')
            ->where('group_id', $groupId)
            ->max('draw_number');

        $next = $lastDraw ? $lastDraw + 1 : 1;

        return response()->json(['next_draw_number' => $next]);
    }


    



}
