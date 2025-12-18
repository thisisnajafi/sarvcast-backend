<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->notificationService = app(NotificationService::class);
    }

    /**
     * Test get user notifications
     */
    public function test_can_get_user_notifications()
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/notifications');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'notifications' => [
                            '*' => [
                                'id',
                                'type',
                                'title',
                                'message',
                                'read_at',
                                'created_at'
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    /**
     * Test mark notification as read
     */
    public function test_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Test mark all notifications as read
     */
    public function test_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/v1/notifications/mark-all-read');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All notifications marked as read'
                ]);

        $this->assertEquals(0, $this->user->notifications()->whereNull('read_at')->count());
    }

    /**
     * Test send in-app notification
     */
    public function test_can_send_in_app_notification()
    {
        $result = $this->notificationService->sendInAppNotification(
            $this->user,
            'Test Title',
            'Test Message',
            'info'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test Message',
            'type' => 'info'
        ]);
    }

    /**
     * Test send bulk notification
     */
    public function test_can_send_bulk_notification()
    {
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $results = $this->notificationService->sendBulkNotification(
            $userIds,
            'Bulk Test Title',
            'Bulk Test Message',
            'info',
            ['in_app']
        );

        $this->assertCount(3, $results);
        $this->assertEquals(3, Notification::where('title', 'Bulk Test Title')->count());
    }

    /**
     * Test send subscription notification
     */
    public function test_can_send_subscription_notification()
    {
        $result = $this->notificationService->sendSubscriptionNotification(
            $this->user,
            'subscription_created',
            ['subscription_id' => 1]
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'اشتراک جدید'
        ]);
    }

    /**
     * Test send content notification
     */
    public function test_can_send_content_notification()
    {
        $result = $this->notificationService->sendContentNotification(
            $this->user,
            'new_episode',
            ['episode_id' => 1, 'story_title' => 'Test Story']
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'اپیزود جدید'
        ]);
    }

    /**
     * Test get unread notification count
     */
    public function test_can_get_unread_notification_count()
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => now()
        ]);

        $count = $this->notificationService->getUnreadCount($this->user);

        $this->assertEquals(2, $count);
    }

    /**
     * Test mark notification as read
     */
    public function test_can_mark_notification_as_read_service()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $result = $this->notificationService->markAsRead($notification);

        $this->assertTrue($result);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test mark all notifications as read for user
     */
    public function test_can_mark_all_notifications_as_read_for_user()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $result = $this->notificationService->markAllAsRead($this->user);

        $this->assertTrue($result);
        $this->assertEquals(0, $this->user->notifications()->whereNull('read_at')->count());
    }

    /**
     * Test clean old notifications
     */
    public function test_can_clean_old_notifications()
    {
        // Create old notification
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(35)
        ]);

        // Create recent notification
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10)
        ]);

        $deletedCount = $this->notificationService->cleanOldNotifications(30);

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, Notification::count());
    }

    /**
     * Test notification filtering
     */
    public function test_can_filter_notifications()
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'info'
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'success'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/notifications?type=info');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'notifications'
                    ]
                ]);
    }

    /**
     * Test notification search
     */
    public function test_can_search_notifications()
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Notification'
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Another Notification'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/notifications?search=Test');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'notifications'
                    ]
                ]);
    }

    /**
     * Test notifications require authentication
     */
    public function test_notifications_require_authentication()
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /**
     * Test email notification (mocked)
     */
    public function test_can_send_email_notification()
    {
        Mail::fake();

        $result = $this->notificationService->sendEmailNotification(
            $this->user,
            'Test Email Subject',
            'emails.notification',
            ['title' => 'Test Title', 'message' => 'Test Message']
        );

        $this->assertTrue($result);
        Mail::assertSent(\Illuminate\Mail\Mailable::class);
    }

    /**
     * Test notification types
     */
    public function test_notification_types()
    {
        $types = ['info', 'success', 'warning', 'error'];

        foreach ($types as $type) {
            $result = $this->notificationService->sendInAppNotification(
                $this->user,
                "Test {$type} Title",
                "Test {$type} Message",
                $type
            );

            $this->assertTrue($result);
        }

        $this->assertEquals(4, Notification::count());
    }

    /**
     * Test notification data storage
     */
    public function test_notification_data_storage()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];

        $this->notificationService->sendInAppNotification(
            $this->user,
            'Test Title',
            'Test Message',
            'info',
            $data
        );

        $notification = Notification::where('user_id', $this->user->id)->first();
        $this->assertEquals($data, $notification->data);
    }
}