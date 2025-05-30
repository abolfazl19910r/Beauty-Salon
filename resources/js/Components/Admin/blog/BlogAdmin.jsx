import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import {
    Newspaper,
    Loader2,
    Trash2,
    PenSquare,
    Eye,
    EyeOff,
    Plus,
    X,
    Save,
    Edit3,
    ImagePlus
} from 'lucide-react';

const BlogAdmin = () => {
    const [posts, setPosts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [selectedImage, setSelectedImage] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [editingPost, setEditingPost] = useState(null);
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [stats, setStats] = useState({
        totalViews: 0,
        postCount: 0,
        categoryCount: 0
    });
    const [newPost, setNewPost] = useState({
        title: '',
        content: '',
        excerpt: '',
        category_id: '',
        is_published: false,
        published_date: '',
        published_time: '',
    });
    const [newCategory, setNewCategory] = useState({
        name: '',
        description: ''
    });
    const [isAddingCategory, setIsAddingCategory] = useState(false);

    const convertToJalaliDate = (gregorianDate) => {
        if (!gregorianDate) return '';
        const date = new Date(gregorianDate);
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Intl.DateTimeFormat('fa-IR', options).format(date);
    };

    const showNotification = (message, type = 'error') => {
        if (type === 'error') {
            setError(message);
            setSuccess(null);
        } else {
            setSuccess(message);
            setError(null);
        }
    };

    const combineDateTime = () => {
        return newPost.published_date && newPost.published_time
            ? `${newPost.published_date} ${newPost.published_time}`
            : null;
    };

    const updateStatsAfterAction = (action) => {
        switch(action) {
            case 'create':
                setStats(prev => ({
                    ...prev,
                    postCount: prev.postCount + 1
                }));
                break;
            case 'delete':
                setStats(prev => ({
                    ...prev,
                    postCount: prev.postCount - 1
                }));
                break;
        }
    };

    const fetchCategories = async () => {
        try {
            setLoading(true);
            const response = await fetch('/admin/blog/categories', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'خطا در دریافت دسته‌بندی‌ها');
            }

            const data = await response.json();
            setCategories(data.data || []);
            setError(null);
        } catch (err) {
            showNotification(err.message);
        } finally {
            setLoading(false);
        }
    };

    const fetchPosts = async () => {
        try {
            const response = await fetch('/admin/blog', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                setPosts(result.data.map(post => ({
                    ...post,
                    image_url: post.image_url || null
                })));

                setStats({
                    totalViews: result.total_views || 0,
                    postCount: result.post_count || 0,
                    categoryCount: result.category_count || 0
                });
            } else {
                throw new Error(result.message || 'خطا در دریافت مقالات');
            }
        } catch (err) {
            console.error('Fetch Error:', err);
            showNotification('خطا در دریافت مقالات: ' + err.message);
        }
    };

    useEffect(() => {
        fetchPosts();
        fetchCategories();
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            setSuccess(null);
            setError(null);
        }, 5000);
        return () => clearTimeout(timer);
    }, [success, error]);

    const resetForm = () => {
        setNewPost({
            title: '',
            content: '',
            excerpt: '',
            category_id: '',
            is_published: false,
            published_date: '',
            published_time: ''
        });
        setSelectedImage(null);
        setImagePreview(null);
        setEditingPost(null);
        setShowCreateForm(false);
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                showNotification('حجم فایل نباید بیش از 2 مگابایت باشد.');
                return;
            }

            if (!file.type.startsWith('image/')) {
                showNotification('لطفاً فقط فایل تصویری انتخاب کنید.');
                return;
            }

            setSelectedImage(file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const validateForm = () => {
        if (!newPost.title.trim()) {
            showNotification('عنوان مقاله الزامی است.');
            return false;
        }
        if (!newPost.content.trim()) {
            showNotification('محتوای مقاله الزامی است.');
            return false;
        }
        if (!newPost.category_id) {
            showNotification('انتخاب دسته‌بندی الزامی است.');
            return false;
        }
        return true;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateForm()) return;

        const postData = {
            ...newPost,
            published_at_jalali: combineDateTime(),
            is_published: newPost.is_published === true ||
                newPost.is_published === 'true' ||
                newPost.is_published === 1
        };

        const formData = new FormData();

        Object.keys(postData).forEach(key => {
            if (postData[key] !== null && postData[key] !== undefined) {
                formData.append(key, postData[key]);
            }
        });

        if (selectedImage) {
            formData.append('image', selectedImage);
        }

        setSubmitting(true);
        setError(null);

        try {
            const url = editingPost
                ? `/admin/blog/${editingPost.id}`
                : '/admin/blog';

            const method = editingPost ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در عملیات');
            }

            const result = await response.json();
            showNotification(
                result.message || (editingPost ? 'مقاله با موفقیت به‌روزرسانی شد!' : 'مقاله با موفقیت ایجاد شد!'),
                'success'
            );

            updateStatsAfterAction(editingPost ? 'edit' : 'create');
            await fetchPosts();
            resetForm();

        } catch (err) {
            showNotification(err.message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleEdit = (post) => {
        setEditingPost(post);
        setNewPost({
            title: post.title,
            content: post.content,
            excerpt: post.excerpt || '',
            category_id: post.category_id,
            is_published: post.is_published,
            published_date: post.published_at ? convertToJalaliDate(post.published_at).split(' ')[0] : '',
            published_time: post.published_at ? convertToJalaliDate(post.published_at).split(' ')[1] : ''
        });
        setShowCreateForm(true);
        setImagePreview(post.image_url || null);
    };

    const handleDelete = async (id) => {
        if (!window.confirm('آیا از حذف این مقاله اطمینان دارید؟ این عمل قابل برگشت نیست.')) return;

        try {
            const response = await fetch(`/admin/blog/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در حذف مقاله');
            }

            const result = await response.json();
            showNotification(result.message || 'مقاله با موفقیت حذف شد!', 'success');
            updateStatsAfterAction('delete');
            await fetchPosts();
        } catch (err) {
            showNotification(err.message);
        }
    };

    const handleTogglePublish = async (id) => {
        try {
            const response = await fetch(`/admin/blog/${id}/publish`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در تغییر وضعیت مقاله');
            }

            const result = await response.json();
            showNotification(result.message || 'وضعیت مقاله با موفقیت تغییر یافت!', 'success');
            await fetchPosts();
        } catch (err) {
            showNotification(err.message);
        }
    };

    const handleCreateCategory = async (e) => {
        e.preventDefault();

        if (!newCategory.name.trim()) {
            showNotification('نام دسته‌بندی الزامی است');
            return;
        }

        try {
            const response = await fetch('/admin/blog/categories', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(newCategory)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در ایجاد دسته‌بندی');
            }

            const result = await response.json();

            setCategories([...categories, result.data]);

            setStats(prev => ({
                ...prev,
                categoryCount: prev.categoryCount + 1
            }));

            setNewCategory({ name: '', description: '' });
            setIsAddingCategory(false);

            showNotification('دسته‌بندی با موفقیت ایجاد شد', 'success');
        } catch (err) {
            showNotification(err.message);
        }
    };

    const handleDeleteCategory = async (id) => {
        if (!window.confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/admin/blog/categories/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در حذف دسته‌بندی');
            }

            setCategories(categories.filter(cat => cat.id !== id));

            setStats(prev => ({
                ...prev,
                categoryCount: prev.categoryCount - 1
            }));

            showNotification('دسته‌بندی با موفقیت حذف شد', 'success');
        } catch (err) {
            showNotification(err.message);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
                <span className="mr-2">در حال بارگذاری...</span>
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Newspaper className="h-6 w-6" />
                    <h2 className="text-2xl font-bold">مدیریت وبلاگ</h2>
                </div>
                <button
                    onClick={() => setShowCreateForm(!showCreateForm)}
                    className="flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                >
                    {showCreateForm ? <X className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
                    {showCreateForm ? 'لغو' : 'مقاله جدید'}
                </button>
            </div>

            <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="bg-white shadow rounded-lg p-4 text-center">
                    <div className="text-gray-500">تعداد مقالات</div>
                    <div className="text-2xl font-bold text-blue-600">{stats.postCount}</div>
                </div>
                <div className="bg-white shadow rounded-lg p-4 text-center">
                    <div className="text-gray-500">تعداد دسته‌بندی‌ها</div>
                    <div className="text-2xl font-bold text-green-600">{stats.categoryCount}</div>
                </div>
                <div className="bg-white shadow rounded-lg p-4 text-center">
                    <div className="text-gray-500">بازدید کل</div>
                    <div className="text-2xl font-bold text-purple-600">{stats.totalViews}</div>
                </div>
            </div>

            {error && (
                <Alert variant="destructive" className="mb-4">
                    <p>{error}</p>
                </Alert>
            )}

            {success && (
                <Alert className="bg-green-100 text-green-800 mb-4">
                    <p>{success}</p>
                </Alert>
            )}

            {showCreateForm && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {editingPost ? <Edit3 className="h-5 w-5" /> : <Plus className="h-5 w-5" />}
                            {editingPost ? 'ویرایش مقاله' : 'افزودن مقاله جدید'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block mb-2 font-medium text-gray-700">
                                        عنوان <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={newPost.title}
                                        onChange={e => setNewPost({...newPost, title: e.target.value})}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        required
                                        placeholder="عنوان مقاله را وارد کنید"
                                    />
                                </div>
                                <div>
                                    <label className="block mb-2 font-medium text-gray-700">
                                        دسته‌بندی <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={newPost.category_id}
                                        onChange={e => setNewPost({...newPost, category_id: e.target.value})}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        required
                                    >
                                        <option value="">انتخاب کنید</option>
                                        {categories.map(category => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block mb-2 font-medium text-gray-700">
                                    محتوا <span className="text-red-500">*</span>
                                </label>
                                <textarea
                                    value={newPost.content}
                                    onChange={e => setNewPost({...newPost, content: e.target.value})}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    rows={5}
                                    required
                                    placeholder="محتوای مقاله را وارد کنید"
                                ></textarea>
                            </div>

                            <div>
                                <label className="block mb-2 font-medium text-gray-700">
                                    چکیده
                                </label>
                                <textarea
                                    value={newPost.excerpt}
                                    onChange={e => setNewPost({...newPost, excerpt: e.target.value})}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    rows={3}
                                    placeholder="چکیده مقاله را وارد کنید (اختیاری)"
                                ></textarea>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block mb-2 font-medium text-gray-700">
                                        تاریخ انتشار
                                    </label>
                                    <input
                                        type="text"
                                        value={newPost.published_date}
                                        onChange={e => setNewPost({...newPost, published_date: e.target.value})}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="مثال: 1404/03/15"
                                    />
                                </div>
                                <div>
                                    <label className="block mb-2 font-medium text-gray-700">
                                        زمان انتشار
                                    </label>
                                    <input
                                        type="text"
                                        value={newPost.published_time}
                                        onChange={e => setNewPost({...newPost, published_time: e.target.value})}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="مثال: 14:30"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block mb-2 font-medium text-gray-700">
                                    تصویر
                                </label>
                                <div className="flex items-center space-x-4">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={handleImageChange}
                                        className="hidden"
                                        id="image-upload"
                                    />
                                    <label
                                        htmlFor="image-upload"
                                        className="flex items-center gap-2 cursor-pointer bg-blue-50 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-100 transition"
                                    >
                                        <ImagePlus className="h-5 w-5" />
                                        انتخاب تصویر
                                    </label>
                                    {imagePreview && (
                                        <div className="w-24 h-24 rounded-lg overflow-hidden">
                                            <img
                                                src={imagePreview}
                                                alt="پیش‌نمایش تصویر"
                                                className="w-full h-full object-cover"
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center space-x-4">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={newPost.is_published}
                                        onChange={e => setNewPost({
                                            ...newPost,
                                            is_published: e.target.checked
                                        })}
                                        className="form-checkbox"
                                    />
                                    <span>منتشر شود</span>
                                </label>
                            </div>

                            <div className="flex items-center gap-3 pt-4 border-t">
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="flex items-center gap-2 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {submitting ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Save className="h-4 w-4" />
                                    )}
                                    {submitting ? 'در حال ذخیره...' : (editingPost ? 'به‌روزرسانی' : 'ایجاد مقاله')}
                                </button>
                                <button
                                    type="button"
                                    onClick={resetForm}
                                    className="flex items-center gap-2 bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition"
                                >
                                    <X className="h-4 w-4" />
                                    لغو
                                </button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>لیست مقالات ({posts.length})</CardTitle>
                </CardHeader>
                <CardContent>
                    {posts.length === 0 ? (
                        <div className="text-center py-12">
                            <p>هیچ مقاله‌ای موجود نیست</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {posts.map(post => (
                                <div key={post.id} className="border p-4 rounded flex justify-between items-center">
                                    <div className="flex items-center">
                                        {post.image_url && (
                                            <img
                                                src={post.image_url}
                                                alt={post.title}
                                                className="w-16 h-16 object-cover rounded-lg ml-4"
                                            />
                                        )}
                                        <div>
                                            <h3 className="font-bold">{post.title}</h3>
                                            <p className="text-gray-600">
                                                {post.category?.name} |
                                                {post.is_published ?
                                                    <span className="text-green-600 mr-2">منتشر شده</span> :
                                                    <span className="text-red-600 mr-2">پیش‌نویس</span>
                                                }
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => handleTogglePublish(post.id)}
                                            className={`text-${post.is_published ? 'red' : 'green'}-600`}
                                            title={post.is_published ? 'پیش‌نویس' : 'انتشار'}
                                        >
                                            {post.is_published ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                        <button
                                            onClick={() => handleEdit(post)}
                                            className="text-blue-600"
                                            title="ویرایش"
                                        >
                                            <PenSquare className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(post.id)}
                                            className="text-red-600"
                                            title="حذف"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
            {/* مدیریت دسته‌بندی‌ها */}
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span>دسته‌بندی‌ها</span>
                        </div>
                        <button
                            onClick={() => setIsAddingCategory(!isAddingCategory)}
                            className="flex items-center gap-2 bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-sm"
                        >
                            {isAddingCategory ? <X className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
                            {isAddingCategory ? 'انصراف' : 'دسته‌بندی جدید'}
                        </button>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {/* فرم ایجاد دسته‌بندی جدید */}
                    {isAddingCategory && (
                        <form
                            onSubmit={handleCreateCategory}
                            className="bg-gray-100 p-4 rounded-lg mb-4"
                        >
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block mb-2 font-medium">نام دسته‌بندی</label>
                                    <input
                                        type="text"
                                        value={newCategory.name}
                                        onChange={(e) => setNewCategory({...newCategory, name: e.target.value})}
                                        className="w-full border rounded p-2"
                                        required
                                        placeholder="نام دسته‌بندی را وارد کنید"
                                    />
                                </div>
                                <div>
                                    <label className="block mb-2 font-medium">توضیحات (اختیاری)</label>
                                    <input
                                        type="text"
                                        value={newCategory.description}
                                        onChange={(e) => setNewCategory({...newCategory, description: e.target.value})}
                                        className="w-full border rounded p-2"
                                        placeholder="توضیحات دسته‌بندی"
                                    />
                                </div>
                            </div>
                            <div className="mt-4 flex justify-end">
                                <button
                                    type="submit"
                                    className="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition"
                                >
                                    ایجاد دسته‌بندی
                                </button>
                            </div>
                        </form>
                    )}

                    {/* لیست دسته‌بندی‌ها */}
                    <div className="space-y-2">
                        {categories.map(category => (
                            <div
                                key={category.id}
                                className="flex justify-between items-center border-b pb-2 last:border-b-0"
                            >
                                <div>
                                    <span className="font-medium">{category.name}</span>
                                    {category.posts_count > 0 && (
                                        <span className="text-sm text-gray-500 mr-2">
                                ({category.posts_count} مقاله)
                            </span>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleDeleteCategory(category.id)}
                                        className="text-red-500 hover:text-red-700"
                                        title="حذف"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};
export default BlogAdmin;
