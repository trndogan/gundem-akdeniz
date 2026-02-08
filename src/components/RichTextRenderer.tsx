import React from 'react';

export function RichTextRenderer({ content }: { content: any }) {
  if (!content) return null;

  if (content.root && content.root.children) {
    return <LexicalRenderer nodes={content.root.children} />;
  }

  if (typeof content === 'string') {
    return <div dangerouslySetInnerHTML={{ __html: content }} />;
  }

  return null;
}

function LexicalRenderer({ nodes }: { nodes: any[] }) {
  return (
    <>
      {nodes.map((node: any, i: number) => {
        switch (node.type) {
          case 'heading': {
            const level = node.tag || 'h2';
            return React.createElement(level, { key: i }, <LexicalRenderer nodes={node.children || []} />);
          }
          case 'paragraph':
            return <p key={i}><LexicalRenderer nodes={node.children || []} /></p>;
          case 'text': {
            let text = <>{node.text}</>;
            if (node.format & 1) text = <strong>{text}</strong>;
            if (node.format & 2) text = <em>{text}</em>;
            if (node.format & 4) text = <s>{text}</s>;
            if (node.format & 8) text = <u>{text}</u>;
            if (node.format & 16) text = <code>{text}</code>;
            return <span key={i}>{text}</span>;
          }
          case 'list': {
            const ListTag = node.listType === 'number' ? 'ol' : 'ul';
            return <ListTag key={i}><LexicalRenderer nodes={node.children || []} /></ListTag>;
          }
          case 'listitem':
            return <li key={i}><LexicalRenderer nodes={node.children || []} /></li>;
          case 'link':
            return (
              <a key={i} href={node.fields?.url || '#'} target={node.fields?.newTab ? '_blank' : undefined} rel={node.fields?.newTab ? 'noopener noreferrer' : undefined}>
                <LexicalRenderer nodes={node.children || []} />
              </a>
            );
          case 'quote':
            return <blockquote key={i}><LexicalRenderer nodes={node.children || []} /></blockquote>;
          case 'upload': {
            const imgUrl = node.value?.url || '';
            const alt = node.value?.alt || '';
            return imgUrl ? <img key={i} src={imgUrl} alt={alt} className="rounded-xl" /> : null;
          }
          case 'linebreak':
            return <br key={i} />;
          default:
            if (node.children) return <LexicalRenderer key={i} nodes={node.children} />;
            return null;
        }
      })}
    </>
  );
}
