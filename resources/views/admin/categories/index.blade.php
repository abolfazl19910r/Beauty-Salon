@extends('layouts.admin')

@section('title', 'مدیریت دسته‌بندی‌ها')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">مدیریت دسته‌بندی‌ها</h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> افزودن دسته‌بندی جدید
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.categories.index') }}" method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>جستجو</label>
                                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="نام دسته‌بندی...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>وضعیت</label>
                                        <select name="status" class="form-control">
                                            <option value="">همه</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>دسته‌بندی والد</label>
                                        <select name="parent_id" class="form-control">
                                            <option value="">همه</option>
                                            @foreach($parentCategories as $parentCategory)
                                                <option value="{{ $parentCategory->id }}" {{ request('parent_id') == $parentCategory->id ? 'selected' : '' }}>
                                                    {{ $parentCategory->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> جستجو
                                            </button>
                                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                                                <i class="fa fa-times"></i> پاک کردن
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        @if($categories->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                    <tr>
                                        <th style="width: 60px">ردیف</th>
                                        <th>نام</th>
                                        <th>دسته والد</th>
                                        <th>ترتیب</th>
                                        <th>وضعیت</th>
                                        <th style="width: 250px">عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($categories as $key => $category)
                                        <tr>
                                            <td>{{ $categories->firstItem() + $key }}</td>
                                            <td>
                                                @if($category->icon)
                                                    <i class="{{ $category->icon }}"></i>
                                                @endif
                                                {{ $category->name }}
                                            </td>
                                            <td>{{ $category->parent ? $category->parent->name : 'دسته اصلی' }}</td>
                                            <td>{{ $category->order }}</td>
                                            <td>
                                                @if($category->is_active)
                                                    <span class="badge badge-success">فعال</span>
                                                @else
                                                    <span class="badge badge-danger">غیرفعال</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i> مشاهده
                                                </a>
                                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i> ویرایش
                                                </a>
                                                <form action="{{ route('admin.categories.toggle-status', $category) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $category->is_active ? 'btn-warning' : 'btn-success' }}">
                                                        <i class="fa {{ $category->is_active ? 'fa-times' : 'fa-check' }}"></i>
                                                        {{ $category->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i> حذف
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $categories->withQueryString()->links() }}
                            </div>
                        @else
                            <div class="alert alert-info">
                                هیچ دسته‌بندی‌ای یافت نشد.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.delete-form').on('submit', function(e) {
                e.preventDefault();
                if (confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟')) {
                    this.submit();
                }
            });
        });
    </script>
@endpush
