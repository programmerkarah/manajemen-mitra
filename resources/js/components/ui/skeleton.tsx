import { cn } from "@/lib/utils"

function Skeleton({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="skeleton"
      className={cn("bg-primary/10 backdrop-blur-sm animate-pulse rounded-lg", className)}
      {...props}
    />
  )
}

export { Skeleton }
