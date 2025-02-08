// resources/js/components/admin/BlogAdmin.jsx
import React, { useState, useEffect } from 'react';
import { Editor } from '@tinymce/tinymce-react';

export const BlogAdmin = () => {
    const [posts, setPosts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [newPost, setNewPost] = useState({
        title: '',
        content: '',
        excerpt: '',
        category_id: '',
        image: null,
        is_published: false,
        published_at: new Date().toISOString().slice(0, 16)
    });

    useEffect(() => {
        fetchPosts();
        fetchCategories();
    }, []);

    const fetchPosts = async () => {
        try {
            const response = await fetch('/api/admin/blog/posts');
            const data = await response.json();
            setPosts(data);
        } catch (error) {
            console.error('Error fetching posts:', error);
        }
    };

    const fetchCategories = async () => {
        try {
            const response = await fetch('/api/admin/blog/categories');
            const data = await response.json();
            setCategories(data);
        } catch (error) {
            console.error('Error fetching categories:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData();

        Object.keys(newPost).forEach(key => {
            if (key === 'image') {
                if (newPost.image) formData.append('image', newPost.image);
            } else {
                formData.append(key, newPost[key]);
            }
        });

        try {
            const response = await fetch('/api/admin/blog/posts', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (response.ok) {
                fetchPosts();
                setNewPost({
                    title: '',
                    content: '',
                    excerpt: '',
                    category_id: '',
                    image: null,
                    is_published: false,
                    published_at: new Date().toISOString().slice(0, 16)
                });
            }
        } catch (error) {
            console.error('Error creating post:', error);
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

            if (response.ok) {
                fetchPosts();
            }
        } catch (error) {
            console.error('Error deleting post:', error);
        }
    };

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold">مدیریت بلاگ</h2>

            {/* فرم افزودن مقاله جدید */}
            <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow">
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

                <div className="mt-4">
                    <label className="block mb-2">خلاصه</label>
                    <textarea
                        value={newPost.excerpt}
                        onChange={e => setNewPost({...newPost, excerpt: e.target.value})}
                        className="w-full border rounded px-3 py-2"
                        rows="3"
                    />
                </div>

                <div className="mt-4">
                    <label className="block mb-2">محتوا</label>
                    <Editor
                        value={newPost.content}
                        onEditorChange={(content) => setNewPost({...newPost, content})}
                        init={{
                            height: 400,
                            menubar: false,
                            plugins: [
                                'advlist autolink lists link image charmap print preview anchor',
                                'searchreplace visualblocks code fullscreen',
                                'insertdatetime media table paste code help wordcount'
                            ],
                            toolbar: 'undo redo | formatselect | bold italic backcolor | \
                                alignleft aligncenter alignright alignjustify | \
                                bullist numlist outdent indent | removeformat | help'
                        }}
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 mt-4">
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
                    <div>
                        <label className="block mb-2">تصویر</label>
                        <input
                            type="file"
                            onChange={e => setNewPost({...newPost, image: e.target.files[0]})}
                            className="w-full border rounded px-3 py-2"
                            accept="image/*"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 mt-4">
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
                    <div className="flex items-center">
                        <input
                            type="checkbox"
                            checked={newPost.is_published}
                            onChange={e => setNewPost({...newPost, is_published: e.target.checked})}
                            className="ml-2"
                        />
                        <label>منتشر شود</label>
                    </div>
                </div>

                <div className="mt-6">
                    <button type="submit" className="bg-blue-500 text-white px-4 py-2 rounded">
                        افزودن مقاله
                    </button>
                </div>
            </form>

            {/* لیست مقالات */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full">
                    <thead>
                    <tr className="bg-gray-50">
                        <th className="px-6 py-3 text-right">عنوان</th>
                        <th className="px-6 py-3">دسته‌بندی</th>
                        <th className="px-6 py-3">تاریخ انتشار</th>
                        <th className="px-6 py-3">وضعیت</th>
                        <th className="px-6 py-3">عملیات</th>
                    </tr>
                    </thead>
                    <tbody className="divide-y">
                    {posts.map(post => (
                        <tr key={post.id}>
                            <td className="px-6 py-4">{post.title}</td>
                            <td className="px-6 py-4">{post.category?.name}</td>
                            <td className="px-6 py-4" dir="ltr">
                                {new Date(post.published_at).toLocaleString('fa-IR')}
                            </td>
                            <td className="px-6 py-4">
                                    <span className={`px-2 py-1 rounded-full ${
                                        post.is_published
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        {post.is_published ? 'منتشر شده' : 'پیش‌نویس'}
                                    </span>
                            </td>
                            <td className="px-6 py-4 space-x-2 space-x-reverse">
                                <button
                                    onClick={() => handleEdit(post.id)}
                                    className="text-blue-500 hover:text-blue-700">
                                    ویرایش
                                </button>
                                <button
                                    onClick={() => handleDelete(post.id)}
                                    className="text-red-500 hover:text-red-700">
                                    حذف
                                </button>
                            </td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export class GalleryAdmin {
}
