export interface PromptSuggestion {
    label: string;
    description: string;
    prompt: string;
}

export const suggestedSystemPrompts: PromptSuggestion[] = [
    {
        label: 'Concise Scholar',
        description: 'Brief, direct answers with inline citations',
        prompt: 'Provide concise, direct answers. Cite papers inline as (Author, Year). Keep responses under 300 words unless asked for detail.',
    },
    {
        label: 'Critical Analyst',
        description: 'Compare findings and highlight contradictions',
        prompt: 'Critically analyze the papers. Compare findings across studies, highlight contradictions or limitations, and note the strength of evidence.',
    },
    {
        label: 'Methodology Focus',
        description: 'Emphasize research methods and sample sizes',
        prompt: 'Focus on research methodology. Mention sample sizes, study designs, and methodological limitations when discussing findings.',
    },
    {
        label: 'Practical Applications',
        description: 'Connect research to real-world implications',
        prompt: 'Connect research findings to practical applications and real-world implications. Explain how the findings could be applied in practice.',
    },
    {
        label: 'Structured Overview',
        description: 'Use headings and bullet points for clarity',
        prompt: 'Structure responses with clear headings (##), bullet points for key findings, and numbered lists for sequential points.',
    },
    {
        label: 'Synthesis First',
        description: 'Lead with the main takeaway',
        prompt: 'Start with a one-sentence synthesis of the key finding across all papers, then elaborate with supporting details from individual studies.',
    },
];

export const suggestedNegativePrompts: PromptSuggestion[] = [
    {
        label: 'No Hedging',
        description: 'Avoid tentative language',
        prompt: 'Do not use hedging language like "it could be argued", "it is possible that", or "this might suggest". Be direct.',
    },
    {
        label: 'No Repetition',
        description: 'Avoid restating the question',
        prompt: 'Do not repeat or paraphrase the question before answering. Start directly with the answer.',
    },
    {
        label: 'No Filler',
        description: 'Skip preamble and conclusions',
        prompt: 'Do not include introductory phrases like "Based on the provided papers" or concluding summaries. Get straight to the content.',
    },
    {
        label: 'No Bullet Points',
        description: 'Use prose paragraphs only',
        prompt: 'Do not use bullet points or numbered lists. Write in clear prose paragraphs with topic sentences.',
    },
    {
        label: 'No References Section',
        description: 'Skip the reference list at the end',
        prompt: 'Do not include a separate References section at the end. Cite inline only.',
    },
    {
        label: 'No Speculation',
        description: 'Stay within the provided papers',
        prompt: 'Do not speculate beyond what the papers directly support. If the papers do not address something, state that clearly rather than inferring.',
    },
];
