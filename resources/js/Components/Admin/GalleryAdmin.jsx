// resources/js/components/admin/GalleryAdmin.jsx
import {useEffect, useState} from "react";

const GalleryAdmin = () => {
    const [images, setImages] = useState([]);
    const [newImage, setNewImage] = useState({
        title: '',
        description: '',
        image: null
    });
    const [reordering, setReordering] = useState(false);

    useEffect(() => {
        fetchImages();
    }, []);

    const fetchImages = async () => {
        try {
            const response = await fetch('/api/admin/gallery');
            const data = await response.json();
            setImages(data);
        } catch (error) {
            console.error('Error fetching images:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData();

        Object.keys(newImage).forEach(key => {
            if (key === 'image') {
                if (newImage.image) formData.append('image', newImage.image);
            } else {
                formData.append(key, newImage[key]);
            }
        });

        try {
            const response = await fetch('/api/admin/gallery', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (response.ok) {
                fetchImages();
                setNewImage({
                    title: '',
                    description: '',
                    image: null
                });
            }
        } catch (error) {
            console.error('Error uploading image:', error);
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

            if (response.ok) {
                fetchImages();
            }
        } catch (error) {
            console.error('Error deleting image:', error);
        }
    };

    const handleReorder = async (reorderedImages) => {
        try {
            const response = await fetch('/api/admin/gallery/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ images: reorderedImages })
            });

            if (response.ok) {
                fetchImages();
                setReordering(false);
            }
        } catch (error) {
            console.error('Error reordering images:', error);
        }
    };

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold">مدیریت گالری</h2>

            {/* فرم آپلود تصویر جدید */}
            <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow">
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

                <div className="mt-4">
                    <label className="block mb-2">توضیحات</label>
                    <textarea
                        value={newImage.description}
                        onChange={e => setNewImage({...newImage, description: e.target.value})}
                        className="w-full border rounded px-3 py-2"
                        rows="3"
                    />
                </div>

                <div className="mt-4">
                    <label className="block mb-2">تصویر</label>
                    <input
                        type="file"
                        onChange={e => setNewImage({...newImage, image: e.target.files[0]})}
                        className="w-full border rounded px-3 py-2"
                        accept="image/*"
                        required
                    />
                </div>

                <div className="mt-6">
                    <button type="submit" className="bg-blue-500 text-white px-4 py-2 rounded">
                        آپلود تصویر
                    </button>
                </div>
            </form>

            {/* نمایش تصاویر */}
            <div className="bg-white rounded-lg shadow p-6">
                <div className="flex justify-end mb-4">
                    <button
                        onClick={() => setReordering(!reordering)}
                        className="bg-purple-500 text-white px-4 py-2 rounded"
                    >
                        {reordering ? 'ذخیره ترتیب' : 'تغییر ترتیب'}
                    </button>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    {images.map(image => (
                        <div key={image.id} className="relative group">
                            <img
                                src={image.image_url}
                                alt={image.title}
                                className="w-full h-48 object-cover rounded"
                            />
                            <div className="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2 space-x-reverse">
                                <button
                                    onClick={() => handleDelete(image.id)}
                                    className="text-white bg-red-500 px-3 py-1 rounded"
                                >
                                    حذف
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export { BlogAdmin, GalleryAdmin };
