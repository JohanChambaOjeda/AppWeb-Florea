import type { SVGAttributes } from 'react';

/**
 * Flor de cinco pétalos. Usa fill-current para tomar el color del texto.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            {[0, 72, 144, 216, 288].map((angle) => (
                <ellipse
                    key={angle}
                    cx="20"
                    cy="10.5"
                    rx="6.5"
                    ry="9"
                    transform={`rotate(${angle} 20 20)`}
                    opacity="0.85"
                />
            ))}
            <circle cx="20" cy="20" r="5" />
        </svg>
    );
}
