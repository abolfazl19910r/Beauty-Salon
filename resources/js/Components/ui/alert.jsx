// resources/js/components/ui/alert.jsx
import * as React from "react"
import { cn } from "@/lib/utils"

const Alert = React.forwardRef(({ variant = "default", className, ...props }, ref) => {
    const variants = {
        default: "bg-white border-gray-200",
        destructive: "border-red-500/50 text-red-600 bg-red-50",
        warning: "border-yellow-500/50 text-yellow-600 bg-yellow-50",
        info: "border-blue-500/50 text-blue-600 bg-blue-50",
        success: "border-green-500/50 text-green-600 bg-green-50"
    }

    return (
        <div
            ref={ref}
            role="alert"
            className={cn(
                "relative w-full rounded-lg border p-4",
                variants[variant],
                className
            )}
            {...props}
        />
    )
})
Alert.displayName = "Alert"

const AlertTitle = React.forwardRef(({ className, ...props }, ref) => (
    <h5
        ref={ref}
        className={cn("mb-1 font-medium leading-none tracking-tight", className)}
        {...props}
    />
))
AlertTitle.displayName = "AlertTitle"

const AlertDescription = React.forwardRef(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn("text-sm [&_p]:leading-relaxed", className)}
        {...props}
    />
))
AlertDescription.displayName = "AlertDescription"

export { Alert, AlertTitle, AlertDescription }
