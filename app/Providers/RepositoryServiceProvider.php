<?php

namespace App\Providers;

use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use App\Repositories\Contracts\BlogPostRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Repositories\Contracts\GalleryImageRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use App\Repositories\Contracts\LoyaltySettingRepositoryInterface;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\ReportExportRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\ReviewTokenRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\SalonSmsUsageRepositoryInterface;
use App\Repositories\Contracts\SecurityLogRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use App\Repositories\Contracts\SpecialistWalletRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserWalletTransactionRepositoryInterface;
use App\Repositories\Contracts\WalletSettingRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use App\Repositories\Eloquent\AnnouncementRepository;
use App\Repositories\Eloquent\BeautyServiceRepository;
use App\Repositories\Eloquent\BlogCategoryRepository;
use App\Repositories\Eloquent\BlogPostRepository;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\DiscountCodeRepository;
use App\Repositories\Eloquent\GalleryImageRepository;
use App\Repositories\Eloquent\HolidayRepository;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\LeaveRepository;
use App\Repositories\Eloquent\LoyaltyPointRepository;
use App\Repositories\Eloquent\LoyaltySettingRepository;
use App\Repositories\Eloquent\NotificationSettingRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\ReportExportRepository;
use App\Repositories\Eloquent\ReviewRepository;
use App\Repositories\Eloquent\ReviewTokenRepository;
use App\Repositories\Eloquent\RewardRepository;
use App\Repositories\Eloquent\SalonRepository;
use App\Repositories\Eloquent\SalonSmsUsageRepository;
use App\Repositories\Eloquent\SecurityLogRepository;
use App\Repositories\Eloquent\SpecialistRepository;
use App\Repositories\Eloquent\SpecialistScheduleRepository;
use App\Repositories\Eloquent\SpecialistWalletRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\UserWalletTransactionRepository;
use App\Repositories\Eloquent\WalletSettingRepository;
use App\Repositories\Eloquent\WalletTransactionRepository;
use App\Repositories\Eloquent\WithdrawalRequestRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    protected array $repositories = [
        CategoryRepositoryInterface::class => CategoryRepository::class,
        BeautyServiceRepositoryInterface::class => BeautyServiceRepository::class,
        SpecialistRepositoryInterface::class => SpecialistRepository::class,
        SpecialistScheduleRepositoryInterface::class => SpecialistScheduleRepository::class,
        LeaveRepositoryInterface::class => LeaveRepository::class,
        HolidayRepositoryInterface::class => HolidayRepository::class,
        BookingRepositoryInterface::class => BookingRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        InvoiceRepositoryInterface::class => InvoiceRepository::class,
        WalletSettingRepositoryInterface::class => WalletSettingRepository::class,
        SpecialistWalletRepositoryInterface::class => SpecialistWalletRepository::class,
        WithdrawalRequestRepositoryInterface::class => WithdrawalRequestRepository::class,
        WalletTransactionRepositoryInterface::class => WalletTransactionRepository::class,
        UserWalletTransactionRepositoryInterface::class => UserWalletTransactionRepository::class,
        DiscountCodeRepositoryInterface::class => DiscountCodeRepository::class,
        RewardRepositoryInterface::class => RewardRepository::class,
        LoyaltyPointRepositoryInterface::class => LoyaltyPointRepository::class,
        LoyaltySettingRepositoryInterface::class => LoyaltySettingRepository::class,
        BlogPostRepositoryInterface::class => BlogPostRepository::class,
        BlogCategoryRepositoryInterface::class => BlogCategoryRepository::class,
        GalleryImageRepositoryInterface::class => GalleryImageRepository::class,
        AnnouncementRepositoryInterface::class => AnnouncementRepository::class,
        ReviewRepositoryInterface::class => ReviewRepository::class,
        ReviewTokenRepositoryInterface::class => ReviewTokenRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        SecurityLogRepositoryInterface::class => SecurityLogRepository::class,
        SalonRepositoryInterface::class => SalonRepository::class,
        SalonSmsUsageRepositoryInterface::class => SalonSmsUsageRepository::class,
        ReportExportRepositoryInterface::class => ReportExportRepository::class,
        NotificationSettingRepositoryInterface::class => NotificationSettingRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
