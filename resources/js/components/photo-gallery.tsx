import { useState } from 'react';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { OfferPhoto } from '@/types';

/**
 * Miniaturas de la foto del día con visor ampliado al tocarlas. La foto es
 * lo que decide una compra, así que tiene que poderse ver grande.
 */
export function PhotoGallery({
    photos,
    size = 'default',
}: {
    photos: OfferPhoto[];
    size?: 'default' | 'sm';
}) {
    const [open, setOpen] = useState<number | null>(null);

    if (photos.length === 0) {
        return null;
    }

    const current = open !== null ? photos[open] : null;

    return (
        <>
            <div className="flex flex-wrap gap-2">
                {photos.map((photo, index) => (
                    <button
                        key={photo.id}
                        type="button"
                        onClick={() => setOpen(index)}
                        className={cn(
                            'overflow-hidden rounded-lg border',
                            size === 'sm' ? 'size-16' : 'size-24',
                        )}
                    >
                        <img
                            src={photo.url}
                            alt="Foto de la flor ofertada"
                            loading="lazy"
                            className="size-full object-cover"
                        />
                    </button>
                ))}
            </div>

            <Dialog
                open={current !== null}
                onOpenChange={(isOpen) => !isOpen && setOpen(null)}
            >
                <DialogContent className="max-w-[95vw] p-2 sm:max-w-3xl">
                    <DialogTitle className="sr-only">Foto ampliada</DialogTitle>
                    {current !== null && (
                        <div className="flex flex-col gap-2">
                            <img
                                src={current.url}
                                alt="Foto de la flor ofertada"
                                className="max-h-[75vh] w-full rounded-lg object-contain"
                            />
                            {photos.length > 1 && (
                                <div className="flex justify-center gap-2">
                                    {photos.map((photo, index) => (
                                        <button
                                            key={photo.id}
                                            type="button"
                                            onClick={() => setOpen(index)}
                                            aria-label={`Foto ${index + 1}`}
                                            className={cn(
                                                'size-2.5 rounded-full',
                                                index === open
                                                    ? 'bg-primary'
                                                    : 'bg-muted-foreground/40',
                                            )}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
