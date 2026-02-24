import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Home, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ErrorProps {
    status: number;
}

export default function Error({ status }: ErrorProps) {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        setIsVisible(true);
    }, []);

    const title =
        {
            503: 'Service Unavailable',
            500: 'Server Error',
            404: 'Page Not Found',
            403: 'Forbidden',
        }[status] || 'An Error Occurred';

    const description =
        {
            503: 'Maaf, sistem sedang dalam perbaikan. Silakan coba lagi nanti.',
            500: 'Maaf, terjadi kesalahan pada server. Tim kami sedang memperbaikinya.',
            404: 'Maaf, halaman yang Anda cari tidak ditemukan atau telah dipindahkan.',
            403: 'Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.',
        }[status] || 'Terjadi kesalahan yang tidak terduga.';

    return (
        <>
            <Head title={`${status} - ${title}`} />

            <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-blue-50 via-white to-purple-50 px-4 py-8">
                {/* Floating background elements */}
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="floating-circle absolute top-20 left-10 h-32 w-32 rounded-full bg-blue-200 opacity-20 blur-xl"></div>
                    <div className="floating-circle-delayed absolute right-20 bottom-20 h-40 w-40 rounded-full bg-purple-200 opacity-20 blur-xl"></div>
                    <div className="floating-circle-slow absolute top-1/2 left-1/4 h-24 w-24 rounded-full bg-pink-200 opacity-20 blur-xl"></div>
                </div>

                <div
                    className={`relative z-10 w-full max-w-2xl text-center transition-all duration-1000 ${
                        isVisible
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-10 opacity-0'
                    }`}
                >
                    {/* Error Code Badge
                    <div className="mb-8 inline-block">
                        <div className="text-8xl font-bold text-blue-600 opacity-90 animate-pulse-slow">
                            {status}
                        </div>
                    </div> */}

                    {/* 503 Image - Only show for 503 errors */}
                    {status === 503 && (
                        <div className="mb-8 flex justify-center">
                            <div className="relative">
                                <div className="animate-pulse-slow absolute inset-0 rounded-full bg-blue-400 opacity-20 blur-3xl"></div>
                                <img
                                    src="/503.png"
                                    alt="503 Service Unavailable"
                                    className="animate-float relative z-10 h-64 w-64 object-contain"
                                />
                            </div>
                        </div>
                    )}

                    {/* 404 Image - Only show for 404 errors */}
                    {status === 404 && (
                        <div className="mb-8 flex justify-center">
                            <div className="relative">
                                <div className="animate-pulse-slow absolute inset-0 rounded-full bg-blue-400 opacity-20 blur-3xl"></div>
                                <img
                                    src="/404.png"
                                    alt="404 Not Found"
                                    className="animate-float relative z-10 h-64 w-64 object-contain"
                                />
                            </div>
                        </div>
                    )}

                    {/* 403 Image - Only show for 403 errors */}
                    {status === 403 && (
                        <div className="mb-8 flex justify-center">
                            <div className="relative">
                                <div className="animate-pulse-slow absolute inset-0 rounded-full bg-blue-400 opacity-20 blur-3xl"></div>
                                <img
                                    src="/403.png"
                                    alt="403 Forbidden"
                                    className="animate-float relative z-10 h-64 w-64 object-contain"
                                />
                            </div>
                        </div>
                    )}

                    {/* 500 Image - Only show for 500 errors */}
                    {status === 500 && (
                        <div className="mb-8 flex justify-center">
                            <div className="relative">
                                <div className="animate-pulse-slow absolute inset-0 rounded-full bg-blue-400 opacity-20 blur-3xl"></div>
                                <img
                                    src="/500.png"
                                    alt="500 Server Error"
                                    className="animate-float relative z-10 h-64 w-64 object-contain"
                                />
                            </div>
                        </div>
                    )}

                    {/* Title */}
                    <h1 className="animate-fade-in-up mb-4 text-4xl font-bold text-gray-800 md:text-5xl">
                        {title}
                    </h1>

                    {/* Description */}
                    <p className="animate-fade-in-up animation-delay-200 mb-8 text-lg text-gray-600">
                        {description}
                    </p>

                    {/* Action Buttons */}
                    <div className="animate-fade-in-up animation-delay-400 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link
                            href="/"
                            className="inline-flex transform items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-blue-700 hover:shadow-xl"
                        >
                            <Home className="h-5 w-5" />
                            Kembali ke Dashboard
                        </Link>

                        <button
                            onClick={() => window.history.back()}
                            className="inline-flex transform items-center gap-2 rounded-lg border border-gray-200 bg-white px-6 py-3 font-semibold text-gray-700 shadow-lg transition-all duration-300 hover:scale-105 hover:bg-gray-50 hover:shadow-xl"
                        >
                            <ArrowLeft className="h-5 w-5" />
                            Halaman Sebelumnya
                        </button>

                        <button
                            onClick={() => window.location.reload()}
                            className="inline-flex transform items-center gap-2 rounded-lg border border-gray-200 bg-white px-6 py-3 font-semibold text-gray-700 shadow-lg transition-all duration-300 hover:scale-105 hover:bg-gray-50 hover:shadow-xl"
                        >
                            <RefreshCw className="h-5 w-5" />
                            Refresh
                        </button>
                    </div>

                    {/* Additional Help Text */}
                    <div className="animate-fade-in animation-delay-600 mt-12 text-sm text-gray-500">
                        <p>Butuh bantuan? Hubungi administrator sistem.</p>
                    </div>
                </div>

                {/* Custom CSS Animations */}
                <style>{`
                    @keyframes float {
                        0%, 100% {
                            transform: translateY(0px) rotate(0deg);
                        }
                        25% {
                            transform: translateY(-20px) rotate(2deg);
                        }
                        50% {
                            transform: translateY(-10px) rotate(-2deg);
                        }
                        75% {
                            transform: translateY(-15px) rotate(1deg);
                        }
                    }

                    @keyframes floating-circle {
                        0%, 100% {
                            transform: translate(0, 0) scale(1);
                        }
                        33% {
                            transform: translate(30px, -30px) scale(1.1);
                        }
                        66% {
                            transform: translate(-20px, 20px) scale(0.9);
                        }
                    }

                    @keyframes fade-in-up {
                        from {
                            opacity: 0;
                            transform: translateY(30px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }

                    @keyframes fade-in {
                        from {
                            opacity: 0;
                        }
                        to {
                            opacity: 1;
                        }
                    }

                    @keyframes pulse-slow {
                        0%, 100% {
                            opacity: 0.9;
                        }
                        50% {
                            opacity: 0.6;
                        }
                    }

                    .animate-float {
                        animation: float 6s ease-in-out infinite;
                    }

                    .floating-circle {
                        animation: floating-circle 8s ease-in-out infinite;
                    }

                    .floating-circle-delayed {
                        animation: floating-circle 10s ease-in-out infinite;
                        animation-delay: 1s;
                    }

                    .floating-circle-slow {
                        animation: floating-circle 12s ease-in-out infinite;
                        animation-delay: 2s;
                    }

                    .animate-fade-in-up {
                        animation: fade-in-up 0.8s ease-out forwards;
                    }

                    .animate-fade-in {
                        animation: fade-in 1s ease-out forwards;
                    }

                    .animate-pulse-slow {
                        animation: pulse-slow 3s ease-in-out infinite;
                    }

                    .animation-delay-200 {
                        animation-delay: 0.2s;
                        opacity: 0;
                    }

                    .animation-delay-400 {
                        animation-delay: 0.4s;
                        opacity: 0;
                    }

                    .animation-delay-600 {
                        animation-delay: 0.6s;
                        opacity: 0;
                    }
                `}</style>
            </div>
        </>
    );
}
