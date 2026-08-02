const modules = import.meta.glob('../data/releases/*.js', {
    eager: true,
    import: 'default'
})

const releases = Object.entries(modules)
    .map(([path, release]) => ({
        ...release,
        sourcePath: path,
    }))
    .sort(
        (a, b) =>
            new Date(b.publishedAt).getTime() -
            new Date(a.publishedAt).getTime(),
    )

export function useReleaseNotes() {
    return readonly(ref(releases))
}