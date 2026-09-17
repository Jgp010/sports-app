import SwiftUI

struct NewsListView: View {
    @State private var posts: [NewsPost] = []
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        Group {
            if let errorMessage, posts.isEmpty {
                ErrorStateView(message: errorMessage) { Task { await load() } }
            } else if posts.isEmpty && !isLoading {
                EmptyStateView(title: "目前沒有消息", icon: "newspaper")
            } else {
                List(posts) { post in
                    NavigationLink(value: post) { NewsRow(post: post) }
                }
                .listStyle(.plain)
                .refreshable { await load() }
                .navigationDestination(for: NewsPost.self) { NewsDetailView(post: $0) }
            }
        }
        .navigationTitle("最新消息")
        .loading(isLoading && posts.isEmpty)
        .task { if posts.isEmpty { await load() } }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            posts = try await APIClient.shared.news()
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private struct NewsRow: View {
    let post: NewsPost

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            AsyncImage(url: post.coverUrl.flatMap(URL.init(string:))) { phase in
                if case .success(let image) = phase {
                    image.resizable().scaledToFill()
                } else {
                    ZStack {
                        Color.indigo.opacity(0.1)
                        Image(systemName: "photo").foregroundStyle(.indigo)
                    }
                }
            }
            .frame(width: 96, height: 76)
            .clipShape(RoundedRectangle(cornerRadius: 10))

            VStack(alignment: .leading, spacing: 5) {
                Text(post.sport.name).font(.caption).foregroundStyle(.indigo)
                Text(post.title).font(.headline).lineLimit(2)
                Text(post.summary).font(.subheadline).foregroundStyle(.secondary).lineLimit(2)
                Text(AppFormatters.dateTime(post.publishedAt)).font(.caption2).foregroundStyle(.tertiary)
            }
        }
        .padding(.vertical, 4)
    }
}

struct NewsDetailView: View {
    let post: NewsPost
    @State private var detail: NewsPost?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                if let url = (detail ?? post).coverUrl.flatMap(URL.init(string:)) {
                    AsyncImage(url: url) { image in
                        image.resizable().scaledToFit()
                    } placeholder: {
                        ProgressView().frame(maxWidth: .infinity, minHeight: 180)
                    }
                    .clipShape(RoundedRectangle(cornerRadius: 14))
                }
                Text((detail ?? post).sport.name).font(.subheadline).foregroundStyle(.indigo)
                Text((detail ?? post).title).font(.largeTitle.bold())
                Label(AppFormatters.dateTime((detail ?? post).publishedAt), systemImage: "calendar")
                    .font(.subheadline).foregroundStyle(.secondary)
                if let venue = (detail ?? post).venue {
                    Label(venue, systemImage: "mappin.and.ellipse").font(.subheadline)
                }
                Divider()
                Text((detail ?? post).content ?? (detail ?? post).summary)
                    .font(.body).lineSpacing(6).textSelection(.enabled)
                if let errorMessage { Text(errorMessage).foregroundStyle(.red).font(.footnote) }
            }
            .padding()
        }
        .navigationTitle("消息詳情")
        .navigationBarTitleDisplayMode(.inline)
        .loading(isLoading)
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do { detail = try await APIClient.shared.newsDetail(slug: post.slug) }
        catch { errorMessage = error.localizedDescription }
    }
}
