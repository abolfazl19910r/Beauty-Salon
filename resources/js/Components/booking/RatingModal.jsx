//resources/js/Components/booking/RatingModal.jsx
import React, { useState } from 'react';
import { Star } from 'lucide-react';

const RatingModal = ({ booking, onClose, onSubmit }) => {
    const [rating, setRating] = useState(0);
    const [review, setReview] = useState('');

    const handleSubmit = async () => {
        try {
            const response = await fetch(`/api/bookings/${booking.id}/rate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ rating, review })
            });

            if (response.ok) {
                onSubmit();
                onClose();
            }
        } catch (error) {
            console.error('Error submitting rating:', error);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div className="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 className="text-lg font-bold mb-4">ثبت نظر</h3>

                {/* Rating Stars */}
                <div className="flex justify-center gap-2 mb-6">
                    {[1, 2, 3, 4, 5].map((star) => (
                        <button
                            key={star}
                            onClick={() => setRating(star)}
                            className="focus:outline-none"
                        >
                            <Star
                                className={`w-8 h-8 ${
                                    star <= rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'
                                }`}
                            />
                        </button>
                    ))}
                </div>

                {/* Review Text */}
                <textarea
                    value={review}
                    onChange={(e) => setReview(e.target.value)}
                    placeholder="نظر خود را بنویسید..."
                    className="w-full border rounded-lg p-2 h-32 mb-4"
                    maxLength={500}
                />

                {/* Actions */}
                <div className="flex justify-end gap-2">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"
                    >
                        انصراف
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={!rating}
                        className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-300"
                    >
                        ثبت نظر
                    </button>
                </div>
            </div>
        </div>
    );
};

export default RatingModal;
