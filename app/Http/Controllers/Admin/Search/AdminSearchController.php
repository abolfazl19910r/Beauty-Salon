<?php

namespace App\Http\Controllers\Admin\Search;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Http\Request;
use Verta;

class AdminSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        $results = [];

        if ($query) {

            $specialists = Specialist::where('name', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get();
            if ($specialists->isNotEmpty()) {
                $results['متخصصین'] = $specialists;
            }

            $services = BeautyService::where('name', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get();
            if ($services->isNotEmpty()) {
                $results['خدمات'] = $services;
            }

            $users = User::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%");
            })
                ->limit(5)
                ->get();

            if ($users->isNotEmpty()) {
                $results['کاربران'] = $users;
            }
        }

        return view('admin.search.index', compact('query', 'results'));
    }

    public function apiSearch(Request $request)
    {
        $query = $request->input('q');
        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'لطفاً حداقل 2 کاراکتر وارد کنید'
            ], 422);
        }

        $results = [
            'bookings' => $this->searchBookings($query),
            'users' => $this->searchUsers($query),
            'services' => $this->searchServices($query),
            'specialists' => $this->searchSpecialists($query),
            'blog_posts' => $this->searchBlogPosts($query),
        ];

        $totalResults = array_sum(array_map('count', $results));

        return response()->json([
            'success' => true,
            'query' => $query,
            'total' => $totalResults,
            'results' => $results
        ]);
    }

    private function searchBookings($query)
    {
        return Booking::with(['user', 'service', 'specialist'])
            ->where(function($q) use ($query) {
                $q->where('id', 'like', "%{$query}%")
                    ->orWhere('payment_reference', 'like', "%{$query}%")
                    ->orWhereHas('user', function($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('phone', 'like', "%{$query}%");
                    })
                    ->orWhereHas('service', function($serviceQuery) use ($query) {
                        $serviceQuery->where('name', 'like', "%{$query}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'type' => 'booking',
                    'title' => "رزرو #{$booking->id} - {$booking->user->name}",
                    'subtitle' => $booking->service->name . ' | ' . Verta::instance($booking->booking_time)->format('Y/m/d H:i'),
                    'status' => $booking->status,
                    'url' => route('admin.bookings.show', $booking->id),
                    'icon' => 'calendar'
                ];
            })
            ->toArray();
    }

    private function searchUsers($query)
    {
        return User::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'title' => $user->name,
                    'subtitle' => $user->phone . ($user->email ? " | {$user->email}" : ''),
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'url' => route('admin.users.show', $user->id),
                    'icon' => 'user'
                ];
            })
            ->toArray();
    }

    private function searchServices($query)
    {
        return BeautyService::with('category')
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'type' => 'service',
                    'title' => $service->name,
                    'subtitle' => ($service->category ? $service->category->name . ' | ' : '') . number_format($service->price) . ' تومان',
                    'url' => route('admin.services.edit', $service->id),
                    'icon' => 'briefcase'
                ];
            })
            ->toArray();
    }

    private function searchSpecialists($query)
    {
        return Specialist::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($specialist) {
                return [
                    'id' => $specialist->id,
                    'type' => 'specialist',
                    'title' => $specialist->name,
                    'subtitle' => $specialist->phone,
                    'url' => route('admin.specialists.show', $specialist->id),
                    'icon' => 'user-check'
                ];
            })
            ->toArray();
    }

    private function searchBlogPosts($query)
    {
        return BlogPost::with('category')
            ->where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($post) {
                return [
                    'id' => $post->id,
                    'type' => 'blog',
                    'title' => $post->title,
                    'subtitle' => $post->category ? $post->category->name : 'بدون دسته‌بندی',
                    'url' => route('admin.blog.edit', $post->id),
                    'icon' => 'file-text'
                ];
            })
            ->toArray();
    }

    public function suggestions(Request $request)
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'suggestions' => []
            ]);
        }

        $suggestions = collect();

        $users = User::where('name', 'like', "%{$query}%")
            ->limit(3)
            ->pluck('name');
        $suggestions = $suggestions->merge($users);

        $services = BeautyService::where('name', 'like', "%{$query}%")
            ->limit(3)
            ->pluck('name');
        $suggestions = $suggestions->merge($services);

        $specialists = Specialist::where('name', 'like', "%{$query}%")
            ->limit(2)
            ->pluck('name');
        $suggestions = $suggestions->merge($specialists);

        return response()->json([
            'suggestions' => $suggestions->unique()->values()->take(10)
        ]);
    }
}
