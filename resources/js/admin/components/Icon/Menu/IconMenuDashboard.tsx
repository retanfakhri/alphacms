import { FC } from 'react';

interface IconMenuDashboardProps {
    className?: string;
    fill?: boolean;
    duotone?: boolean;
}

const IconMenuDashboard: FC<IconMenuDashboardProps> = ({ className, duotone = true }) => {
    return (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className={className}>
            <path opacity={duotone ? '0.5' : '1'} d="M2 13.5V20C2 21.1046 2.89543 22 4 22H10V14C10 12.8954 10.8954 12 12 12C13.1046 12 14 12.8954 14 14V22H20C21.1046 22 22 21.1046 22 20V13.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
            <path d="M20 11.5L13.4322 3.10099C12.6957 2.1585 11.3043 2.1585 10.5678 3.10099L4 11.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
        </svg>
    );
};

export default IconMenuDashboard;
