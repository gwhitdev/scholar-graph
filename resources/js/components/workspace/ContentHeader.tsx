interface ContentHeaderProps {
    projectName: string;
    projectDescription?: string;
    hasSearched: boolean;
    paperCount: number;
    chatCount: number;
    onEditPrompt: () => void;
}

const steps = [
    { key: 'find', label: 'Find', hint: 'search OpenAlex' },
    { key: 'collect', label: 'Collect', hint: '' },
    { key: 'discuss', label: 'Discuss', hint: 'ask the library' },
] as const;

export function ContentHeader({
    projectName,
    projectDescription,
    hasSearched,
    paperCount,
    chatCount,
    onEditPrompt,
}: ContentHeaderProps) {
    // Determine active step
    let activeStep: string = 'find';
    if (hasSearched) activeStep = 'find';
    if (paperCount > 0) activeStep = 'collect';
    if (chatCount > 0) activeStep = 'discuss';

    const stepCounts: Record<string, string> = {
        find: '',
        collect: paperCount > 0 ? `${paperCount} paper${paperCount === 1 ? '' : 's'}` : '',
        discuss: chatCount > 0 ? `${chatCount} message${chatCount === 1 ? '' : 's'}` : '',
    };

    return (
        <div className="shrink-0 px-8 pt-5">
            {/* Top row: breadcrumb + edit prompt */}
            <div className="flex items-center justify-between">
                <div
                    className="flex items-center gap-2 text-[13px]"
                    style={{ color: 'var(--ws-faint)' }}
                >
                    <span>Projects</span>
                    <span style={{ opacity: 0.6 }}>/</span>
                    <span style={{ color: 'var(--ws-muted)', fontWeight: 500 }}>
                        {projectName}
                    </span>
                </div>
                <button
                    type="button"
                    onClick={onEditPrompt}
                    className="flex items-center gap-2 rounded-[10px] border px-3.5 py-2 text-[13.5px] font-medium transition-opacity hover:opacity-80"
                    style={{
                        background: 'var(--ws-panel)',
                        borderColor: 'var(--ws-line)',
                        color: 'var(--ws-fg)',
                    }}
                >
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <circle cx="10" cy="10" r="2.4" stroke="currentColor" strokeWidth="1.5" />
                        <path
                            d="M10 3.5v2M10 14.5v2M3.5 10h2M14.5 10h2M5.4 5.4l1.4 1.4M13.2 13.2l1.4 1.4M14.6 5.4l-1.4 1.4M6.8 13.2l-1.4 1.4"
                            stroke="currentColor"
                            strokeWidth="1.4"
                            strokeLinecap="round"
                        />
                    </svg>
                    Edit prompt
                </button>
            </div>

            {/* Title + description */}
            <div className="mt-4">
                <h2
                    className="font-serif text-[32px] font-medium tracking-tight"
                    style={{ color: 'var(--ws-fg)', letterSpacing: '-0.015em' }}
                >
                    {projectName}
                </h2>
                {projectDescription && (
                    <p
                        className="mt-1.5 max-w-[60ch] text-sm leading-relaxed"
                        style={{ color: 'var(--ws-muted)' }}
                    >
                        {projectDescription}
                    </p>
                )}
            </div>

            {/* Workflow tabs */}
            <div
                className="mt-5 flex items-center gap-0 border-b"
                style={{ borderColor: 'var(--ws-line)' }}
            >
                {steps.map((step, idx) => {
                    const isActive = step.key === activeStep;
                    const isCompleted =
                        (step.key === 'find' && hasSearched) ||
                        (step.key === 'collect' && paperCount > 0) ||
                        (step.key === 'discuss' && chatCount > 0);

                    return (
                        <div
                            key={step.key}
                            className="relative flex items-center gap-2 px-5 pb-3 pr-5"
                        >
                            <span
                                className="flex size-[22px] items-center justify-center rounded-full font-mono text-[12px] font-semibold"
                                style={
                                    isActive
                                        ? {
                                              background: 'var(--ws-accent)',
                                              color: 'var(--ws-onacc)',
                                          }
                                        : isCompleted
                                          ? {
                                                background: 'var(--ws-soft)',
                                                color: 'var(--ws-accent)',
                                            }
                                          : {
                                                background: 'var(--ws-panel2)',
                                                color: 'var(--ws-muted)',
                                            }
                                }
                            >
                                {isCompleted && !isActive ? (
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
                                className="text-[13px] font-semibold"
                                style={{ color: 'var(--ws-fg)' }}
                            >
                                {step.label}
                            </span>
                            <span
                                className="text-[12.5px]"
                                style={{ color: 'var(--ws-faint)' }}
                            >
                                {isActive && stepCounts[step.key]
                                    ? stepCounts[step.key]
                                    : !isActive && isCompleted
                                      ? ''
                                      : step.hint}
                            </span>
                            {/* Active underline indicator */}
                            {isActive && (
                                <span
                                    className="absolute right-4 bottom-0 left-4 h-[2px] rounded-full"
                                    style={{ background: 'var(--ws-accent)' }}
                                />
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
