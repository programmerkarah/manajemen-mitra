import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({
    className = '',
    alt = 'SIMANTIK',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/favicon.svg"
            alt={alt}
            className={`object-contain ${className}`}
            {...props}
        />
    );
}
