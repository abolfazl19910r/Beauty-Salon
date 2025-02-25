import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import { Loader2, Trash2, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';

const GalleryAdmin = () => {
    const [images, setImages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [reordering, setReordering] = useState(false);
    const [selectedImage, setSelectedImage] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [newImage, setNewImage] = useState({
        title: '',
        description: '',
        order: 0
    });

    useEffect(() => {
        fetchImages();
    }, []);

    const fetchImages = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/admin/gallery');
            if (!response.ok) throw new Error('خطا در دریافت تصاویر');
            const data = await response.json();
            setImages(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                setError('حجم تصویر نباید بیشتر از 2 مگابایت باشد');
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

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!selectedImage) {
            setError('لطفاً یک تصویر انتخاب کنید');
            return;
        }

        const formData = new FormData();
        formData.append('image', selectedImage);
        formData.append('title', newImage.title);
        formData.append('description', newImage.description);
        formData.append('order', images.length + 1);

        try {
            const response = await fetch('/api/admin/gallery', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (!response.ok) throw new Error('خطا در آپلود تصویر');

            await fetchImages();
            setNewImage({
                title: '',
                description: '',
                order: 0
            });
            setSelectedImage(null);
            setImagePreview(null);
            setError(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('آیا از حذف این تصویر اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/api/admin/gallery/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) throw new Error('خطا در حذف تصویر');

            await fetchImages();
        } catch (err) {
            setError(err.message);
        }
    };

    const moveImage = (index, direction) => {
        if (!reordering) return;

        const newImages = [...images];
        if (direction === 'up' && index > 0) {
            [newImages[index], newImages[index - 1]] = [newImages[index - 1], newImages[index]];
            newImages[index].order = index;
            newImages[index - 1].order = index - 1;
        } else if (direction === 'down' && index < images.length - 1) {
            [newImages[index], newImages[index + 1]] = [newImages[index + 1], newImages[index]];
            newImages[index].order = index;
            newImages[index + 1].order = index + 1;
        }
        setImages(newImages);
    };

    const saveReordering = async () => {
        if (!reordering) return;

        try {
            const response = await fetch('/api/admin/gallery/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    images: images.map((img, index) => ({
                        id: img.id,
                        order: index + 1
                    }))
                })
            });

            if (!response.ok) throw new Error('خطا در ذخیره ترتیب تصاویر');

            setReordering(false);
            await fetchImages();
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
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <h2 className="text-2xl font-bold">مدیریت گالری</h2>
                </div>
                {reordering ? (
                    <button
                        onClick={saveReordering}
                        className="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition flex items-center gap-2"
                    >
                        <ArrowUpDown className="h-5 w-5" />
                        ذخیره ترتیب
                    </button>
                ) : (
                    <button
                        onClick={() => setReordering(true)}
                        className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition flex items-center gap-2"
                    >
                        <ArrowUpDown className="h-5 w-5" />
                        تغییر ترتیب
                    </button>
                )}
            </div>

            {error && (
                <Alert variant="destructive">
                    <p>{error}</p>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>افزودن تصویر جدید</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block mb-2">تصویر</label>
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
                                        alt="پیش‌نمایش"
                                        className="h-32 w-32 object-cover rounded"
                                    />
                                </div>
                            )}
                        </div>

                        <div>
                            <label className="block mb-2">عنوان</label>
                            <input
                                type="text"
                                value={newImage.title}
                                onChange={e => setNewImage({...newImage, title: e.target.value})}
                                className="w-full border rounded px-3 py-2"
                                required
                            />
                        </div>

                        <div>
                            <label className="block mb-2">توضیحات</label>
                            <textarea
                                value={newImage.description}
                                onChange={e => setNewImage({...newImage, description: e.target.value})}
                                className="w-full border rounded px-3 py-2"
                                rows="3"
                            />
                        </div>

                        <div>
                            <button
                                type="submit"
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                            >
                                آپلود تصویر
                            </button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>گالری تصاویر</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {images.map((image, index) => (
                            <div
                                key={image.id}
                                className={`relative group rounded-lg overflow-hidden border ${
                                    reordering ? 'cursor-move' : ''
                                }`}
                            >
                                <img
                                    src={image.image_url}
                                    alt={image.title}
                                    className="w-full h-48 object-cover"
                                />
                                <div className="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    {reordering ? (
                                        <div className="flex flex-col gap-2">
                                            <button
                                                onClick={() => moveImage(index, 'up')}
                                                disabled={index === 0}
                                                className={`p-2 rounded ${
                                                    index === 0
                                                        ? 'bg-gray-400 cursor-not-allowed'
                                                        : 'bg-white hover:bg-gray-100'
                                                }`}
                                            >
                                                <ArrowUp className="h-5 w-5" />
                                            </button>
                                            <button
                                                onClick={() => moveImage(index, 'down')}
                                                disabled={index === images.length - 1}
                                                className={`p-2 rounded ${
                                                    index === images.length - 1
                                                        ? 'bg-gray-400 cursor-not-allowed'
                                                        : 'bg-white hover:bg-gray-100'
                                                }`}
                                            >
                                                <ArrowDown className="h-5 w-5" />
                                            </button>
                                        </div>
                                    ) : (
                                        <button
                                            onClick={() => handleDelete(image.id)}
                                            className="bg-red-500 text-white p-2 rounded hover:bg-red-600 transition"
                                        >
                                            <Trash2 className="h-5 w-5" />
                                        </button>
                                    )}
                                </div>
                                <div className="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white p-2">
                                    <h3 className="font-semibold">{image.title}</h3>
                                    {image.description && (
                                        <p className="text-sm text-gray-300 truncate">
                                            {image.description}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default GalleryAdmin;
