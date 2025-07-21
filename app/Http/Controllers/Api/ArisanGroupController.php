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
        $userId = $request->user()->id;

        $groups = arisan_group::withCount('participants')
            ->paginate($request->get('per_page', 10));

        $groups->getCollection()->transform(function ($group) use ($userId) {
            $group->joined = $group->participants()
                ->where('user_id', $userId)
                ->exists();
            return $group;
        });

        return response()->json([
            'data' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'total' => $groups->total()
            ]
        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    // Laravel Controller
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

        $user = Auth::user();

        $arisanGroups = new arisan_group();
        $arisanGroups->name = $request->name;
        $arisanGroups->code = hexdec(uniqid());
        $arisanGroups->amount = $request->amount;
        $arisanGroups->duration = $request->duration;
        $arisanGroups->start_date = $request->start_date;
        $arisanGroups->end_date = $request->end_date;
        $arisanGroups->user_id = $user->id;
        $arisanGroups->current_drawer_user_id = $user->id;

        // kontrak belum di-deploy
        $arisanGroups->contract_address = null;

        $arisanGroups->save();

        arisan_participant::create([
            'user_id' => $user->id,
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

        $currentCount = arisan_participant::where('group_id', $group->id)->count();

        if ($currentCount >= 30) {
            return response()->json([
                'status' => 'error',
                'message' => 'Grup ini sudah mencapai jumlah maksimal 30 anggota.',
            ], 422);
        }

        // ⬇️ CEK: apakah sudah pernah draw
        $hasDrawStarted = DB::table('arisan_draws')
            ->where('group_id', $group->id)
            ->exists();

        if ($hasDrawStarted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Arisan sudah dimulai (sudah ada draw), tidak bisa join lagi.',
            ], 422);
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
                'participant.wallet_address as user_wallet',
                // tambahkan subquery untuk menghitung peserta
                DB::raw('(SELECT COUNT(*) FROM arisan_participants WHERE group_id = arisan_groups.id) as participants_count')
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


    public function updateContract(Request $request, $id)
    {
        $request->validate([
            'contract_address' => 'required|string'
        ]);

        $group = arisan_group::findOrFail($id);
        $group->contract_address = $request->contract_address;
        $group->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Contract address updated',
            'data' => $group
        ]);
    }


    public function pay(Request $request, $groupId)
    {
        $userId = auth()->id();

        // ambil participant record
        $participant = DB::table('arisan_participants')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return response()->json(['message' => 'Kamu belum terdaftar di grup ini'], 400);
        }

        // tandai has_paid
        DB::table('arisan_participants')
            ->where('id', $participant->id)
            ->update(['has_paid' => true, 'updated_at' => now()]);

        // ambil ronde saat ini (dari smart contract atau DB)
        $lastDraw = DB::table('arisan_draws')
            ->where('group_id', $groupId)
            ->max('draw_number');

        $currentRound = $lastDraw ? $lastDraw + 1 : 1;

        // masukkan ke tabel arisan_payments
        DB::table('arisan_payments')->insert([
            'group_id' => $groupId,
            'user_id' => $userId,
            'round' => $currentRound,
            'amount' => 0.1,
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Pembayaran berhasil dicatat']);
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

        // reset semua peserta jadi belum bayar untuk ronde berikutnya
        DB::table('arisan_participants')
            ->where('group_id', $groupId)
            ->update(['has_paid' => 0]);

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


    public function updateDrawer(Request $request, $id)
    {
        $group = arisan_group::findOrFail($id);

        // Cari peserta berikutnya yang belum menang
        $participants = arisan_participant::where('group_id', $id)->get();

        if ($participants->isEmpty()) {
            return response()->json(['message' => 'Tidak ada peserta.'], 400);
        }

        // urutkan peserta supaya bergilir
        $currentDrawerId = $group->current_drawer_user_id;
        $currentIndex = $participants->pluck('user_id')->search($currentDrawerId);

        $nextIndex = ($currentIndex + 1) % $participants->count();
        $nextDrawerId = $participants[$nextIndex]->user_id;

        $group->current_drawer_user_id = $nextDrawerId;
        $group->save();

        return response()->json([
            'message' => 'Drawer updated',
            'next_drawer_user_id' => $nextDrawerId
        ]);
    }



}
