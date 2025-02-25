import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import { Newspaper, Loader2, Trash2, PenSquare } from 'lucide-react';

const BlogAdmin = () => {
    const [posts, setPosts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedImage, setSelectedImage] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [newPost, setNewPost] = useState({
        title: '',
        content: '',
        excerpt: '',
        category_id: '',
        is_published: false,
        published_at: new Date().toISOString().slice(0, 16)
    });

    useEffect(() => {
        fetchPosts();
        fetchCategories();
    }, []);

    const fetchPosts = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/admin/blog/posts');
            if (!response.ok) throw new Error('خطا در دریافت مقالات');
            const data = await response.json();
            setPosts(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const fetchCategories = async () => {
        try {
            const response = await fetch('/api/admin/blog/categories');
            if (!response.ok) throw new Error('خطا در دریافت دسته‌بندی‌ها');
            const data = await response.json();
            setCategories(data);
        } catch (err) {
            console.error('Error fetching categories:', err);
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setSelectedImage(file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData();

        Object.keys(newPost).forEach(key => {
            formData.append(key, newPost[key]);
        });

        if (selectedImage) {
            formData.append('image', selectedImage);
        }

        try {
            const response = await fetch('/api/admin/blog/posts', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (!response.ok) throw new Error('خطا در ایجاد مقاله جدید');

            await fetchPosts();
            setNewPost({
                title: '',
                content: '',
                excerpt: '',
                category_id: '',
                is_published: false,
                published_at: new Date().toISOString().slice(0, 16)
            });
            setSelectedImage(null);
            setImagePreview(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('آیا از حذف این مقاله اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/api/admin/blog/posts/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) throw new Error('خطا در حذف مقاله');

            await fetchPosts();
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6">
            <div className="flex items-center gap-2">
                <Newspaper className="h-6 w-6" />
                <h2 className="text-2xl font-bold">مدیریت وبلاگ</h2>
            </div>

            {error && (
                <Alert variant="destructive">
                    <p>{error}</p>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>افزودن مقاله جدید</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block mb-2">عنوان</label>
                                <input
                                    type="text"
                                    value={newPost.title}
                                    onChange={e => setNewPost({...newPost, title: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block mb-2">دسته‌بندی</label>
                                <select
                                    value={newPost.category_id}
                                    onChange={e => setNewPost({...newPost, category_id: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
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
                            <label className="block mb-2">خلاصه</label>
                            <textarea
                                value={newPost.excerpt}
                                onChange={e => setNewPost({...newPost, excerpt: e.target.value})}
                                className="w-full border rounded px-3 py-2"
                                rows="3"
                            />
                        </div>

                        <div>
                            <label className="block mb-2">محتوا</label>
                            <textarea
                                value={newPost.content}
                                onChange={e => setNewPost({...newPost, content: e.target.value})}
                                className="w-full border rounded px-3 py-2"
                                rows="10"
                                dir="rtl"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block mb-2">تصویر شاخص</label>
                                <input
                                    type="file"
                                    onChange={handleImageChange}
                                    className="w-full border rounded px-3 py-2"
                                    accept="image/*"
                                />
                                {imagePreview && (
                                    <div className="mt-2">
                                        <img
                                            src={imagePreview}
                                            alt="Preview"
                                            className="h-32 w-32 object-cover rounded"
                                        />
                                    </div>
                                )}
                            </div>
                            <div>
                                <label className="block mb-2">تاریخ انتشار</label>
                                <input
                                    type="datetime-local"
                                    value={newPost.published_at}
                                    onChange={e => setNewPost({...newPost, published_at: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                    required
                                />
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                checked={newPost.is_published}
                                onChange={e => setNewPost({...newPost, is_published: e.target.checked})}
                                className="ml-2"
                            />
                            <label>منتشر شود</label>
                        </div>

                        <div>
                            <button
                                type="submit"
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                            >
                                افزودن مقاله
                            </button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>لیست مقالات</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عنوان</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">دسته‌بندی</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">تاریخ انتشار</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">وضعیت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عملیات</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {posts.map(post => (
                                <tr key={post.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">{post.title}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{post.category?.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap" dir="ltr">
                                        {new Date(post.published_at).toLocaleString('fa-IR')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                post.is_published
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}>
                                                {post.is_published ? 'منتشر شده' : 'پیش‌نویس'}
                                            </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => handleEdit(post.id)}
                                                className="text-blue-600 hover:text-blue-900"
                                            >
                                                <PenSquare className="h-5 w-5" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(post.id)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                <Trash2 className="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default BlogAdmin;
