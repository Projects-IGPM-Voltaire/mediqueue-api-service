<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\CreateScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\HealthCenter;
use App\Models\Schedule;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function store(CreateScheduleRequest $request)
    {
        $schedule = null;
        try {
            $payload = $request->all();
            $userID = $payload['user_id'] ?? null;
            $healthCenterID = $payload['health_center_id'];
            $healthCenter = HealthCenter::findOrFail($healthCenterID);
            $todayScheduleCount = Schedule::where(
                'health_center_id',
                $healthCenterID
            )
                ->whereDate('created_at', today())
                ->get()
                ->count();
            $patientNumber = sprintf('%04d', $todayScheduleCount);
            $barangay = strtoupper(
                $healthCenter->address->barangay->name ?? 'barangay'
            );
            $barangay = preg_replace('/\s+/', '', $barangay);
            $currentDate = now()->format('ymd');
            $referenceNumber = "MQ-{$barangay}-{$currentDate}-{$patientNumber}";
            $dbPayload = [
                'health_center_id' => $payload['health_center_id'],
                'date' => $payload['date'],
                'time_from' => $payload['time_from'],
                'time_to' => $payload['time_to'],
                'patient_number' => $patientNumber,
                'reference_number' => $referenceNumber,
            ];
            if (isset($userID)) {
                $user = User::findorFail($userID);
                $dbPayload = array_merge($dbPayload, [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'birthday' => $user->birthday,
                    'user_id' => $userID,
                ]);
            } else {
                $dbPayload = array_merge($dbPayload, [
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'birthday' => $payload['birthday'],
                ]);
            }
            $schedule = Schedule::create($dbPayload);

            return customResponse()
                ->data($schedule)
                ->message('Create request done.')
                ->success()
                ->generate();
        } catch (Exception $e) {
            $schedule?->delete();
            return customResponse()
                ->data([])
                ->message($e->getMessage())
                ->success()
                ->generate();
        }
    }

    public function index(Request $request)
    {
        try {
            $payload = $request->all();
            $query = Schedule::query();
            $healthCenterID = $payload['health_center_id'] ?? null;
            $userID = $payload['user_id'] ?? null;
            $sortBy = $payload['sort_by'] ?? 'desc';
            if (isset($healthCenterID)) {
                $query->where('health_center_id', $healthCenterID);
            }
            if (isset($userID)) {
                $query->where('user_id', $userID);
            }
            $schedules = $query->orderBy('id', $sortBy)->get();
            return customResponse()
                ->data($schedules)
                ->message('List request done.')
                ->success()
                ->generate();
        } catch (Exception $e) {
            return customResponse()
                ->data([])
                ->message($e->getMessage())
                ->success()
                ->generate();
        }
    }

    public function show($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            return customResponse()
                ->data($schedule)
                ->message('Get request done.')
                ->success()
                ->generate();
        } catch (Exception $e) {
            return customResponse()
                ->data($e->getMessage())
                ->message($e->getMessage())
                ->success()
                ->generate();
        }
    }

    public function update(UpdateScheduleRequest $request, $id)
    {
        try {
            $payload = $request->all();
            $schedule = Schedule::findOrFail($id);
            $schedule->update([
                'date' => $payload['date'],
                'time_from' => $payload['time_from'],
                'time_to' => $payload['time_to'],
            ]);

            return customResponse()
                ->data($schedule)
                ->message('Update request done.')
                ->success()
                ->generate();
        } catch (Exception $e) {
            return customResponse()
                ->data($e->getMessage())
                ->message($e->getMessage())
                ->success()
                ->generate();
        }
    }

    public function destroy($id)
    {
        try {
            $user = Schedule::findOrFail($id);
            $user->delete();
            return customResponse()
                ->data($user)
                ->message('Delete request done.')
                ->success()
                ->generate();
        } catch (Exception $e) {
            return customResponse()
                ->data($e->getMessage())
                ->message($e->getMessage())
                ->success()
                ->generate();
        }
    }

    public function getByReferenceNumber($referenceNumber)
    {
        try {
            $schedule = Schedule::where(
                'reference_number',
                $referenceNumber
            )->first();
            if (empty($schedule)) {
                throw new Exception('Schedule not found');
            }
            return customResponse()
                ->data($schedule)
                ->message('Get request done.')
                ->success()
                ->generate();
        } catch (Exception $e) {
            return customResponse()
                ->data($e->getMessage())
                ->message($e->getMessage())
                ->success()
                ->generate();
        }
    }
}
