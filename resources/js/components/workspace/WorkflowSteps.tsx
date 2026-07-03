interface WorkflowStepsProps {
    hasSearched: boolean;
    paperCount: number;
    chatCount: number;
}

const steps = [
    { key: 'find', label: 'Find' },
    { key: 'library', label: 'Library' },
    { key: 'discuss', label: 'Discuss' },
] as const;

export function WorkflowSteps({ hasSearched, paperCount, chatCount }: WorkflowStepsProps) {
    const completedSteps = new Set<string>();
    if (hasSearched) completedSteps.add('find');
    if (paperCount > 0) {
        completedSteps.add('find');
        completedSteps.add('library');
    }
    if (chatCount > 0) {
        completedSteps.add('find');
        completedSteps.add('library');
        completedSteps.add('discuss');
    }

    // Determine current step (first incomplete step)
    const currentStep = steps.find((s) => !completedSteps.has(s.key))?.key ?? 'discuss';

    return (
        <div className="flex flex-col gap-0.5">
            {steps.map((step, idx) => {
                const isCompleted = completedSteps.has(step.key);
                const isCurrent = step.key === currentStep;

                return (
                    <div
                        key={step.key}
                        className="flex items-center gap-2.5 rounded-[9px] px-2.5 py-2"
                        style={isCurrent ? { background: 'var(--ws-soft)' } : undefined}
                    >
                        <span
                            className="flex size-5 items-center justify-center rounded-full font-mono text-[11px]"
                            style={
                                isCompleted
                                    ? {
                                          background: 'var(--ws-accent)',
                                          color: 'var(--ws-onacc)',
                                      }
                                    : {
                                          border: '1.5px solid var(--ws-line)',
                                          color: 'var(--ws-faint)',
                                      }
                            }
                        >
                            {isCompleted ? (
                                <svg
                                    width="12"
                                    height="12"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5 10l3.5 3.5L15 6.5"
                                        stroke="currentColor"
                                        strokeWidth="1.7"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                </svg>
                            ) : (
                                idx + 1
                            )}
                        </span>
                        <span
                            className="text-[13.5px]"
                            style={{
                                color: isCurrent ? 'var(--ws-fg)' : 'var(--ws-muted)',
                                fontWeight: isCurrent ? 600 : 400,
                            }}
                        >
                            {step.label}
                        </span>
                        <span className="flex-1" />
                        {isCurrent && step.key !== 'find' && (
                            <span
                                className="text-[11.5px] font-semibold"
                                style={{ color: 'var(--ws-accent)' }}
                            >
                                {step.key === 'library' ? paperCount : chatCount}
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
