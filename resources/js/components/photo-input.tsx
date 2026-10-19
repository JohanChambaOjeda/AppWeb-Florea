import { Camera, ImageIcon, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { OfferPhoto } from '@/types';

type Props = {
    /** Fotos ya guardadas que se conservan. */
    existing: OfferPhoto[];
    keptIds: number[];
    onKeepChange: (ids: number[]) => void;
    /** Fotos nuevas de esta sesión. */
    files: File[];
    onFilesChange: (files: File[]) => void;
    max: number;
};

/**
 * Foto del día. En el celular se abre la cámara de atrás con un toque, pero
 * también se puede elegir de la galería: son dos inputs distintos porque
 * capture="environment" impide el acceso a la galería en varios navegadores.
 */
export function PhotoInput({
    existing,
    keptIds,
    onKeepChange,
    files,
    onFilesChange,
    max,
}: Props) {
    const [zoomed, setZoomed] = useState<string | null>(null);

    const kept = existing.filter((photo) => keptIds.includes(photo.id));

    // Las miniaturas de los archivos nuevos son URLs temporales: hay que
    // liberarlas para no dejar memoria colgada.
    const previews = useMemo(
        () => files.map((file) => ({ file, url: URL.createObjectURL(file) })),
        [files],
    );

    useEffect(
        () => () => previews.forEach(({ url }) => URL.revokeObjectURL(url)),
        [previews],
    );

    const total = kept.length + files.length;
    const room = Math.max(0, max - total);

    const add = (picked: FileList | null) => {
        if (picked === null) {
            return;
        }

        onFilesChange([...files, ...Array.from(picked).slice(0, room)]);
    };

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap gap-2">
                {kept.map((photo) => (
                    <Thumb
                        key={`saved-${photo.id}`}
                        url={photo.url}
                        onZoom={() => setZoomed(photo.url)}
                        onRemove={() =>
                            onKeepChange(
                                keptIds.filter((id) => id !== photo.id),
                            )
                        }
                    />
                ))}

                {previews.map(({ file, url }, index) => (
                    <Thumb
                        key={`new-${index}-${file.name}`}
                        url={url}
                        onZoom={() => setZoomed(url)}
                        onRemove={() =>
                            onFilesChange(files.filter((_, i) => i !== index))
                        }
                    />
                ))}
            </div>

            {room > 0 && (
                <div className="flex flex-wrap gap-2">
                    <PickerButton
                        icon={Camera}
                        label="Tomar foto"
                        capture
                        onPick={add}
                    />
                    <PickerButton
                        icon={ImageIcon}
                        label="Elegir de galería"
                        onPick={add}
                    />
                </div>
            )}

            <p className="text-xs text-muted-foreground">
                {total === 0
                    ? `Sube de 1 a ${max} fotos de la flor de hoy.`
                    : `${total} de ${max} fotos.`}
            </p>

            <Dialog
                open={zoomed !== null}
                onOpenChange={(open) => !open && setZoomed(null)}
            >
                <DialogContent className="max-w-[95vw] p-2 sm:max-w-2xl">
                    <DialogTitle className="sr-only">Foto ampliada</DialogTitle>
                    {zoomed !== null && (
                        <img
                            src={zoomed}
                            alt="Foto de la flor"
                            className="max-h-[80vh] w-full rounded-lg object-contain"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}

function Thumb({
    url,
    onZoom,
    onRemove,
}: {
    url: string;
    onZoom: () => void;
    onRemove: () => void;
}) {
    return (
        <div className="relative">
            <button
                type="button"
                onClick={onZoom}
                className="block size-24 overflow-hidden rounded-lg border"
            >
                <img
                    src={url}
                    alt="Foto de la flor"
                    className="size-full object-cover"
                />
            </button>
            <button
                type="button"
                onClick={onRemove}
                aria-label="Quitar foto"
                className="absolute -top-2 -right-2 flex size-7 items-center justify-center rounded-full bg-neutral-900 text-white shadow"
            >
                <X className="size-4" />
            </button>
        </div>
    );
}

function PickerButton({
    icon: Icon,
    label,
    capture = false,
    onPick,
}: {
    icon: typeof Camera;
    label: string;
    capture?: boolean;
    onPick: (files: FileList | null) => void;
}) {
    return (
        <label
            className={cn(
                'flex h-12 cursor-pointer items-center gap-2 rounded-lg border-2 border-input px-4 text-base font-medium',
                'hover:bg-accent',
            )}
        >
            <Icon className="size-5" />
            {label}
            <input
                type="file"
                accept="image/*"
                multiple
                {...(capture ? { capture: 'environment' as const } : {})}
                onChange={(e) => {
                    onPick(e.target.files);
                    e.target.value = '';
                }}
                className="hidden"
            />
        </label>
    );
}
