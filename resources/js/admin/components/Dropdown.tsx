import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { forwardRef, useImperativeHandle, useState } from 'react';

const Dropdown = (props: any, forwardedRef: any) => {
    const [open, setOpen] = useState(false);

    useImperativeHandle(forwardedRef, () => ({
        close() {
            setOpen(false);
        },
    }));

    return (
        <DropdownMenu.Root open={open} onOpenChange={setOpen}>
            <DropdownMenu.Trigger asChild>
                <button type="button" className={props.btnClassName}>
                    {props.button}
                </button>
            </DropdownMenu.Trigger>

            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    sideOffset={props.offset ? props.offset[1] : 8}
                    align={props.placement?.includes('start') ? 'start' : 'end'}
                    className="z-50 min-w-[120px] rounded bg-white p-0 py-2 shadow dark:bg-[#1b2e4b] text-black dark:text-white-dark animate-in fade-in zoom-in duration-200"
                    onClick={() => setOpen(false)}
                >
                    {props.children}
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
};

export default forwardRef(Dropdown);
